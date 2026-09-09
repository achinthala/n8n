<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Controller\Cart;

use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Dcw\RequestQuote\Service\DiscountThresholdService;
use Dcw\RequestQuote\Service\QuoteChangeLogger;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Controller to remove discount from quote when customer confirms
 * Uses the same logic as admin removeModificators to restore original prices
 */
class RemoveDiscount implements HttpPostActionInterface
{
    /**
     * @param QuoteRepositoryInterface $quoteRepository
     * @param DiscountThresholdService $discountThresholdService
     * @param QuoteChangeLogger $changeLogger
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param ResultFactory $resultFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param FormKeyValidator $formKeyValidator
     * @param Cart $cart
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     * @param UrlInterface $url
     */
    public function __construct(
        private readonly QuoteRepositoryInterface $quoteRepository,
        private readonly DiscountThresholdService $discountThresholdService,
        private readonly QuoteChangeLogger $changeLogger,
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ResultFactory $resultFactory,
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly Cart $cart,
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger,
        private readonly UrlInterface $url
    ) {
    }

    /**
     * Remove discount from quote and restore original prices (like admin removeModificators)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        // Validate form key for security
        if (!$this->formKeyValidator->validate($this->request)) {
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([
                'error' => true,
                'message' => __('Invalid form key. Please refresh the page and try again.')
            ]);
        }

        $quoteId = (int)$this->request->getParam('quote_id');

        if (!$quoteId) {
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([
                'error' => true,
                'message' => __('Quote ID is required.')
            ]);
        }

        $discountRemoved = false;
        $oldDiscount = null;
        try {
            // IMPORTANT:
            // `amasty_quote` uses the "checkout" DB connection (resource="checkout")
            // while `quote_item` uses the default connection.
            $quoteItemConnection = $this->resource->getConnection(); // default
            // Some environments might not have a separate "checkout" connection configured.
            // Fallback to default connection if "checkout" is unavailable.
            try {
                $amastyConnection = $this->resource->getConnection('checkout');
            } catch (\Exception $e) {
                $amastyConnection = $quoteItemConnection;
            }
            $amastyQuoteTable = $this->resource->getTableName('amasty_quote');
            $itemTable = $this->resource->getTableName('quote_item');

            // The popup passes the Amasty quote ID already, so treat it as the actual quote ID.
            $actualQuoteId = $quoteId;

            // Get cart quote (checkout session quote)
            $cartQuote = $this->cart->getQuote();

            // Get old discount value before removing it (for logging)
            // Also get remarks to verify admin_note exists
            $oldDataSelect = $amastyConnection->select()
                ->from($amastyQuoteTable, ['discount', 'remarks'])
                ->where('quote_id = ?', $actualQuoteId)
                ->limit(1);
            $oldData = $amastyConnection->fetchRow($oldDataSelect);
            $oldDiscount = ($oldData && isset($oldData['discount']) && $oldData['discount'] !== null && $oldData['discount'] !== '') 
                ? (float)$oldData['discount'] 
                : null;
            $oldRemarksJson = ($oldData && isset($oldData['remarks'])) ? $oldData['remarks'] : null;
            
            // Load original quote using the actual quote ID
            $originalQuote = $this->loadOriginalQuote($actualQuoteId);
            if (!$originalQuote) {
                throw new LocalizedException(__('Quote not found.'));
            }
            
            // Get all items from the original quote (after loadOriginalQuote has set original_quote_item_price)
            $items = $originalQuote->getAllItems();
            $cartItems = $cartQuote->getAllItems();
            
            // First, restore prices on original quote items using original_quote_item_price or original_custom_price from database
            // Read original_quote_item_price or original_custom_price from database and update all price fields
            // Get all items for this quote from database to ensure we process all items
            // Use actualQuoteId (which may be relation_parent_id if editing)
            $allItemIds = $quoteItemConnection->fetchCol(
                $quoteItemConnection->select()
                    ->from($itemTable, ['item_id'])
                    ->where('quote_id = ?', $actualQuoteId)
                    ->where('parent_item_id IS NULL')
            );
            
            foreach ($allItemIds as $itemId) {
                $itemId = (int)$itemId;
                if (!$itemId) {
                    continue;
                }
                
                // Get original price from database - try original_quote_item_price first, then original_custom_price
                $itemData = $quoteItemConnection->fetchRow(
                    $quoteItemConnection->select()
                        ->from($itemTable, ['original_quote_item_price', 'original_custom_price', 'qty'])
                        ->where('item_id = ?', $itemId)
                        ->limit(1)
                );
                
                if (!$itemData) {
                    $this->logger->warning('Item data not found in database', [
                        'quote_id' => $quoteId,
                        'item_id' => $itemId
                    ]);
                    continue;
                }
                
                $originalPrice = null;
                $priceSource = null;
                
                // First try original_quote_item_price
                if (!empty($itemData['original_quote_item_price']) && $itemData['original_quote_item_price'] > 0) {
                    $originalPrice = (float)$itemData['original_quote_item_price'];
                    $priceSource = 'original_quote_item_price';
                }
                // Then try original_custom_price
                elseif (!empty($itemData['original_custom_price']) && $itemData['original_custom_price'] > 0) {
                    $originalPrice = (float)$itemData['original_custom_price'];
                    $priceSource = 'original_custom_price';
                }
                
                if ($originalPrice && $originalPrice > 0) {
                    $qty = (float)($itemData['qty'] ?? 1);
                    
                    // Update all price fields directly in the database FIRST
                    $data = [
                        'price' => $originalPrice,
                        'custom_price' => $originalPrice,
                        'base_price' => $originalPrice,
                        'original_quote_item_price' => $originalPrice,
                        'original_custom_price' => $originalPrice,
                        'row_total' => $originalPrice * $qty,
                        'base_row_total' => $originalPrice * $qty
                    ];
                    $where = ['item_id = ?' => $itemId];
                    $quoteItemConnection->update($itemTable, $data, $where);
                } else {
                    $this->logger->warning('Original price not found in database for item', [
                        'quote_id' => $quoteId,
                        'item_id' => $itemId,
                        'original_quote_item_price' => $itemData['original_quote_item_price'] ?? 'not found',
                        'original_custom_price' => $itemData['original_custom_price'] ?? 'not found'
                    ]);
                }
            }
            
            // Now update the in-memory item objects after database update
            foreach ($items as $originalItem) {
                if ($originalItem->getParentItemId()) {
                    continue;
                }
                
                $itemId = (int)$originalItem->getId();
                if (!$itemId) {
                    continue;
                }
                
                // Get the updated price from database
                $itemData = $quoteItemConnection->fetchRow(
                    $quoteItemConnection->select()
                        ->from($itemTable, ['price', 'original_quote_item_price', 'original_custom_price', 'qty'])
                        ->where('item_id = ?', $itemId)
                        ->limit(1)
                );
                
                if ($itemData) {
                    $originalPrice = null;
                    if (!empty($itemData['original_quote_item_price']) && $itemData['original_quote_item_price'] > 0) {
                        $originalPrice = (float)$itemData['original_quote_item_price'];
                    } elseif (!empty($itemData['original_custom_price']) && $itemData['original_custom_price'] > 0) {
                        $originalPrice = (float)$itemData['original_custom_price'];
                    } elseif (!empty($itemData['price']) && $itemData['price'] > 0) {
                        $originalPrice = (float)$itemData['price'];
                    }
                    
                    if ($originalPrice && $originalPrice > 0) {
                        $qty = (float)($itemData['qty'] ?? $originalItem->getQty());
                        
                        // Update the item object to match database
                        $originalItem->setPrice($originalPrice);
                        $originalItem->setCustomPrice($originalPrice);
                        $originalItem->setBasePrice($originalPrice);
                        $originalItem->setOriginalQuoteItemPrice($originalPrice);
                        $originalItem->setOriginalCustomPrice($originalPrice);
                        $originalItem->setRowTotal($originalPrice * $qty);
                        $originalItem->setBaseRowTotal($originalPrice * $qty);
                        $originalItem->setHasDataChanges(true);
                    }
                }
            }
            
            // Reload items from database to ensure we have the updated prices
            $originalQuote->getItemsCollection()->clear();
            $originalQuote->getItemsCollection()->load();
            
            // Recalculate totals for original quote before saving
            $originalQuote->setTotalsCollectedFlag(false);
            $originalQuote->collectTotals();
            
            // Then, restore prices on cart items by matching with original quote items (like admin)
            // Get original prices from database for cart items
            foreach ($cartItems as $cartItem) {
                if ($cartItem->getParentItemId()) {
                    continue;
                }
                
                $cartItemId = (int)$cartItem->getId();
                if (!$cartItemId) {
                    continue;
                }
                
                // Find corresponding original quote item by SKU to get the original price
                $originalPrice = null;
                foreach ($items as $originalItem) {
                    if ($originalItem->getSku() == $cartItem->getSku()) {
                        // Get original price from database for the original item
                        $originalItemId = (int)$originalItem->getId();
                        if ($originalItemId) {
                            $originalItemData = $quoteItemConnection->fetchRow(
                                $quoteItemConnection->select()
                                    ->from($itemTable, ['original_quote_item_price', 'original_custom_price'])
                                    ->where('item_id = ?', $originalItemId)
                                    ->limit(1)
                            );
                            
                            if ($originalItemData) {
                                // First try original_quote_item_price
                                if (!empty($originalItemData['original_quote_item_price']) && $originalItemData['original_quote_item_price'] > 0) {
                                    $originalPrice = (float)$originalItemData['original_quote_item_price'];
                                }
                                // Then try original_custom_price
                                elseif (!empty($originalItemData['original_custom_price']) && $originalItemData['original_custom_price'] > 0) {
                                    $originalPrice = (float)$originalItemData['original_custom_price'];
                                }
                            }
                        }
                        break;
                    }
                }
                
                if ($originalPrice && $originalPrice > 0) {
                    $qty = (float)$cartItem->getQty();
                    
                    // First, update the cart item object to prevent recalculation from overwriting
                    $cartItem->setPrice($originalPrice);
                    $cartItem->setCustomPrice($originalPrice);
                    $cartItem->setBasePrice($originalPrice);
                    $cartItem->setOriginalQuoteItemPrice($originalPrice);
                    $cartItem->setOriginalCustomPrice($originalPrice);
                    $cartItem->setRowTotal($originalPrice * $qty);
                    $cartItem->setBaseRowTotal($originalPrice * $qty);
                    $cartItem->setHasDataChanges(true);
                    
                    // Then update cart item prices directly in database
                    $cartItemData = [
                        'price' => $originalPrice,
                        'custom_price' => $originalPrice,
                        'base_price' => $originalPrice,
                        'original_quote_item_price' => $originalPrice,
                        'original_custom_price' => $originalPrice,
                        'row_total' => $originalPrice * $qty,
                        'base_row_total' => $originalPrice * $qty
                    ];
                    $cartItemWhere = ['item_id = ?' => $cartItemId];
                    $quoteItemConnection->update($itemTable, $cartItemData, $cartItemWhere);
                }
            }
            
            // Reload cart items from database to ensure we have the updated prices
            $cartQuote->getItemsCollection()->clear();
            $cartQuote->getItemsCollection()->load();
            
            // Reset discount and surcharge in database FIRST (before any saves)
            // This ensures the discount is cleared in the database before quote objects are saved
            // Use actualQuoteId (which may be relation_parent_id if editing)
            $this->resetDiscountOvercharge($actualQuoteId);
            
            // Set discount to 0 on both quote objects BEFORE collectTotals (like admin)
            $originalQuote->setDiscount(0);
            $originalQuote->setData('discount', 0);
            $cartQuote->setDiscount(0);
            $cartQuote->setData('discount', 0);
            
            // Clear original quantities from items
            $this->discountThresholdService->clearOriginalQtyFromItems($originalQuote);
            
            // After updating prices in DB, reload items to ensure in-memory objects have correct prices
            $originalQuote->getItemsCollection()->clear();
            $originalQuote->getItemsCollection()->load();
            $cartQuote->getItemsCollection()->clear();
            $cartQuote->getItemsCollection()->load();
            
            // Now set prices on all items again after reload to ensure they're correct
            foreach ($originalQuote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                $itemId = (int)$item->getId();
                if (!$itemId) {
                    continue;
                }
                
                // Get original price from database
                $itemData = $quoteItemConnection->fetchRow(
                    $quoteItemConnection->select()
                        ->from($itemTable, ['original_quote_item_price', 'original_custom_price', 'price'])
                        ->where('item_id = ?', $itemId)
                        ->limit(1)
                );
                
                $originalPrice = null;
                if ($itemData) {
                    if (!empty($itemData['original_quote_item_price']) && $itemData['original_quote_item_price'] > 0) {
                        $originalPrice = (float)$itemData['original_quote_item_price'];
                    } elseif (!empty($itemData['original_custom_price']) && $itemData['original_custom_price'] > 0) {
                        $originalPrice = (float)$itemData['original_custom_price'];
                    } elseif (!empty($itemData['price']) && $itemData['price'] > 0) {
                        $originalPrice = (float)$itemData['price'];
                    }
                }
                
                if ($originalPrice && $originalPrice > 0) {
                    $qty = (float)$item->getQty();
                    $item->setPrice($originalPrice);
                    $item->setCustomPrice($originalPrice);
                    $item->setBasePrice($originalPrice);
                    $item->setOriginalQuoteItemPrice($originalPrice);
                    $item->setRowTotal($originalPrice * $qty);
                    $item->setBaseRowTotal($originalPrice * $qty);
                    $item->setHasDataChanges(true);
                }
            }
            
            foreach ($cartQuote->getAllItems() as $cartItem) {
                if ($cartItem->getParentItemId()) {
                    continue;
                }
                $cartItemId = (int)$cartItem->getId();
                if (!$cartItemId) {
                    continue;
                }
                
                // Find matching original item by SKU
                $originalPrice = null;
                foreach ($originalQuote->getAllItems() as $originalItem) {
                    if ($originalItem->getSku() == $cartItem->getSku()) {
                        $originalPrice = $originalItem->getPrice();
                        break;
                    }
                }
                
                if ($originalPrice && $originalPrice > 0) {
                    $qty = (float)$cartItem->getQty();
                    $cartItem->setPrice($originalPrice);
                    $cartItem->setCustomPrice($originalPrice);
                    $cartItem->setBasePrice($originalPrice);
                    $cartItem->setOriginalQuoteItemPrice($originalPrice);
                    $cartItem->setRowTotal($originalPrice * $qty);
                    $cartItem->setBaseRowTotal($originalPrice * $qty);
                    $cartItem->setHasDataChanges(true);
                }
            }
            
            // Recalculate totals after restoring prices and removing discount
            $originalQuote->setTotalsCollectedFlag(false);
            $originalQuote->collectTotals();
            $cartQuote->setTotalsCollectedFlag(false);
            $cartQuote->collectTotals();
            
            // Before saving, ensure the quote object doesn't have remarks data that would overwrite our update
            // We'll update remarks directly in the database after save
            if (method_exists($originalQuote, 'getRemarks')) {
                $currentRemarks = $originalQuote->getRemarks();
                if ($currentRemarks) {
                    // Temporarily clear remarks on the object to prevent it from being saved
                    $originalQuote->setRemarks(null);
                }
            }
            
            // Ensure discount is still 0 on quote objects before saving
            $originalQuote->setDiscount(0);
            $originalQuote->setData('discount', 0);
            $cartQuote->setDiscount(0);
            $cartQuote->setData('discount', 0);
            
            // Save original quote first
            $this->quoteRepository->save($originalQuote);
            
            // Save cart quote with restored prices
            $this->cart->save();
            
            // Clear discount and remove admin_note from remarks AFTER saving quote
            // This ensures the quote save doesn't overwrite our remarks update
            // Also ensures discount is 0 even if save somehow restored it
            // Use actualQuoteId (which may be relation_parent_id if editing)
            $this->clearDiscountAndRemarks($actualQuoteId);
            
            // Double-check: Verify discount is 0 in database after all saves
            $verifyDiscount = $amastyConnection->fetchOne(
                $amastyConnection->select()
                    ->from($amastyQuoteTable, ['discount'])
                    ->where('quote_id = ?', $actualQuoteId)
                    ->limit(1)
            );
            
            if ((float)$verifyDiscount > 0) {
                // If discount is still > 0, force it to 0
                $amastyConnection->update(
                    $amastyQuoteTable,
                    ['discount' => 0],
                    ['quote_id = ?' => $actualQuoteId]
                );
            }

            // Mark as removed if DB now shows 0
            $verifyDiscountAfter = $amastyConnection->fetchOne(
                $amastyConnection->select()
                    ->from($amastyQuoteTable, ['discount'])
                    ->where('quote_id = ?', $actualQuoteId)
                    ->limit(1)
            );
            $discountRemoved = ($verifyDiscountAfter !== false && (float)$verifyDiscountAfter <= 0.0);
            
            // Verify admin_note was removed from remarks - try multiple times if needed
            $maxRetries = 3;
            $retryCount = 0;
            $adminNoteRemoved = false;
            
            while ($retryCount < $maxRetries) {
                $verifyRemarksSelect = $amastyConnection->select()
                    ->from($amastyQuoteTable, ['remarks'])
                    ->where('quote_id = ?', $actualQuoteId)
                    ->limit(1);
                $verifyRemarksJson = $amastyConnection->fetchOne($verifyRemarksSelect);
                
                if (!$verifyRemarksJson) {
                    // No remarks = admin_note removed (or never existed)
                    $adminNoteRemoved = true;
                    break;
                }
                
                // Check if admin_note still exists in remarks
                $verifyRemarks = json_decode((string)$verifyRemarksJson, true);
                $hasAdminNote = false;
                
                if (is_array($verifyRemarks)) {
                    $hasAdminNote = isset($verifyRemarks['admin_note']) || $this->hasAdminNoteRecursive($verifyRemarks);
                } else if (stripos((string)$verifyRemarksJson, 'admin_note') !== false) {
                    $hasAdminNote = true;
                }
                
                if (!$hasAdminNote) {
                    $adminNoteRemoved = true;
                    break;
                }
                
                // Try to remove it again using different strategies
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    // Try PHP-based removal again
                    $this->clearDiscountAndRemarks($actualQuoteId);
                    
                    // Also try direct SQL approach if MySQL supports JSON functions
                    try {
                        $this->removeAdminNoteViaSql($actualQuoteId);
                    } catch (\Exception $sqlException) {
                        // SQL approach failed, continue with PHP approach
                    }
                }
            }
            
            if (!$adminNoteRemoved) {
                $this->logger->warning('admin_note could not be removed after multiple attempts', [
                    'quote_id' => $actualQuoteId,
                    'retries' => $retryCount
                ]);
            }
            
            // Log discount removal in Quote Change History
            // Only log if there was actually a discount to remove (oldDiscount > 0)
            if ($discountRemoved && $oldDiscount !== null && (float)$oldDiscount > 0) {
                try {
                    $this->changeLogger->logDiscountChanged(
                        $actualQuoteId,
                        ['discount' => (float)$oldDiscount],
                        [
                            'discount' => 0,
                            'reason'   => 'threshold_limit_reached'
                        ]
                    );
                } catch (\Exception $logException) {
                    // Don't fail discount removal if logging fails
                    $this->logger->warning('Failed to log discount removal', [
                        'quote_id' => $actualQuoteId,
                        'old_discount' => $oldDiscount,
                        'error' => $logException->getMessage()
                    ]);
                }
            }

            // Check if this is an AJAX request
            $isAjax = $this->request->isAjax() 
                || $this->request->getHeader('X-Requested-With') === 'XMLHttpRequest';
            
            if ($isAjax) {
                // For AJAX requests, return JSON so JavaScript can retry the quantity update
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData([
                    'success' => true,
                    'message' => __('Discount has been removed.'),
                    'quote_id' => $actualQuoteId
                ]);
            } else {
                // For non-AJAX requests, redirect to cart page
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('amasty_quote/cart');
                return $resultRedirect;
            }

        } catch (\Exception $e) {
            $this->logger->error('Error removing discount from quote', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);

            // If discount was actually cleared, don't block the UI with an error.
            // This can happen if a later non-critical step fails (e.g. logging).
            try {
                $fallbackConnection = null;
                try {
                    $fallbackConnection = $this->resource->getConnection('checkout');
                } catch (\Exception $inner) {
                    $fallbackConnection = $this->resource->getConnection();
                }

                $amastyQuoteTable = $this->resource->getTableName('amasty_quote');
                $discountInDb = $fallbackConnection->fetchOne(
                    $fallbackConnection->select()
                        ->from($amastyQuoteTable, ['discount'])
                        ->where('quote_id = ?', (int)$quoteId)
                        ->limit(1)
                );

                if ($discountInDb !== false && (float)$discountInDb <= 0.0) {
                    // Best-effort: write change history log even if we hit an exception after removing discount
                    try {
                        $old = $oldDiscount;
                        // Only log if we have a valid old discount value (> 0)
                        // Don't log if old is null or 0, as that would show "None → 0%" which is misleading
                        if ($old !== null && (float)$old > 0) {
                            $this->changeLogger->logDiscountChanged(
                                (int)$quoteId,
                                ['discount' => (float)$old],
                                [
                                    'discount' => 0,
                                    'reason'   => 'threshold_limit_reached'
                                ]
                            );
                        }
                    } catch (\Exception $logException) {
                        // Ignore logging errors
                        $this->logger->warning('Failed to log discount removal in exception handler', [
                            'quote_id' => $quoteId,
                            'error' => $logException->getMessage()
                        ]);
                    }
                    $resultJson = $this->resultJsonFactory->create();
                    return $resultJson->setData([
                        'success' => true,
                        'message' => __('Discount has been removed.'),
                        'quote_id' => (int)$quoteId
                    ]);
                }
            } catch (\Exception $inner) {
                // swallow - fall through to error response
            }

            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([
                'error' => true,
                'message' => __('An error occurred while removing the discount. Please try again.')
            ]);
        }
    }

    /**
     * Load original quote and ensure original_quote_item_price is set (like admin)
     *
     * @param int $quoteId
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    protected function loadOriginalQuote(int $quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $quote->getItemsCollection()->load();
            
            $connection = $this->resource->getConnection();
            $itemTable = $this->resource->getTableName('quote_item');
           
            $prepareData = [];
            // Iterate through each item in the quote
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($quoteItem->getParentItemId()) {
                    continue;
                }
                
                $itemId = (int)$quoteItem->getId();
                if (!$itemId) {
                    continue;
                }
                
                // First, try to get original_quote_item_price from database directly
                $originalPriceFromDb = $connection->fetchOne(
                    $connection->select()
                        ->from($itemTable, ['original_quote_item_price'])
                        ->where('item_id = ?', $itemId)
                        ->limit(1)
                );
                
                $originalPrice = null;
                
                // If original_quote_item_price exists in DB, use it
                if ($originalPriceFromDb && $originalPriceFromDb > 0) {
                    $originalPrice = (float)$originalPriceFromDb;
                    $quoteItem->setData('original_quote_item_price', $originalPrice);
                } elseif (!$quoteItem->getData('original_quote_item_price')) {
                    // If not in DB, try to get from item data
                    // But be careful - custom_price and base_price might already be discounted
                    if ($quoteItem->getData('custom_price') && $quoteItem->getData('custom_price') > 0) {
                        $originalPrice = (float)$quoteItem->getData('custom_price');
                    } elseif ($quoteItem->getData('base_price') && $quoteItem->getData('base_price') > 0) {
                        $originalPrice = (float)$quoteItem->getData('base_price');
                    } elseif ($quoteItem->getPrice() && $quoteItem->getPrice() > 0) {
                        // Last resort - use current price
                        $originalPrice = (float)$quoteItem->getPrice();
                    }
                    
                    if ($originalPrice && $originalPrice > 0) {
                        // Set on item object
                        $quoteItem->setData('original_quote_item_price', $originalPrice);
                        
                        // Also save directly to database to ensure it's persisted
                        $data = ['original_quote_item_price' => $originalPrice];
                        $where = ['item_id = ?' => $itemId];
                        $connection->update($itemTable, $data, $where);
                    }
                } else {
                    // Already has original_quote_item_price set on item
                    $originalPrice = (float)$quoteItem->getData('original_quote_item_price');
                    
                    // Ensure it's also in the database
                    if ($originalPrice > 0) {
                        $existingInDb = $connection->fetchOne(
                            $connection->select()
                                ->from($itemTable, ['original_quote_item_price'])
                                ->where('item_id = ?', $itemId)
                                ->limit(1)
                        );
                        
                        if (!$existingInDb || $existingInDb != $originalPrice) {
                            $data = ['original_quote_item_price' => $originalPrice];
                            $where = ['item_id = ?' => $itemId];
                            $connection->update($itemTable, $data, $where);
                        }
                    }
                }
                
                if ($originalPrice && $originalPrice > 0) {
                    $prepareData[] = $originalPrice;
                }
            }
 
            if (!$quote->getOriginalQuoteItemPrice()) { 
                $implodeData = implode(',', $prepareData);
                $quote->setOriginalQuoteItemPrice($implodeData);
            }
            // Save the changes to quote items
            $this->quoteRepository->save($quote);

            return $quote;
        } catch (\Exception $e) {
            $this->logger->error('Error loading original quote', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get original price from quote items by SKU (like admin getQuoteItemPrice)
     *
     * @param array $items
     * @param string $sku
     * @return float
     */
    /**
     * Get original price from quote items by SKU (exactly like admin getQuoteItemPrice)
     * This method is used for cart items - gets price from already loaded items
     *
     * @param array $items
     * @param string $sku
     * @return float
     */
    protected function getQuoteItemPrice(array $items, string $sku): float
    {
        foreach ($items as $item) {
            if ($item->getSku() == $sku) {
                // First try original_quote_item_price (like admin)
                $originalPrice = $item->getData('original_quote_item_price');

                if (!empty($originalPrice) && $originalPrice > 0) {
                    return (float)$originalPrice;
                }
                
                // Try original_custom_price
                $originalCustomPrice = $item->getData('original_custom_price');
                if (!empty($originalCustomPrice) && $originalCustomPrice > 0) {
                    return (float)$originalCustomPrice;
                }

                // Fall back to getPrice() if original_quote_item_price is empty (like admin)
                if ($item->getPrice() != 0) {
                    return (float)$item->getPrice();
                }
            }
        }

        return 0.0;
    }

    /**
     * Reset discount and surcharge in database (like admin)
     *
     * @param int $quoteId
     * @return void
     */
    protected function resetDiscountOvercharge(int $quoteId): void
    {
        try {
            // `amasty_quote` table uses the "checkout" connection
            try {
                $connection = $this->resource->getConnection('checkout');
            } catch (\Exception $e) {
                $connection = $this->resource->getConnection();
            }
            $tableName = $this->resource->getTableName('amasty_quote');
           
            // Prepare the data to update
            $data = ['discount' => 0, 'surcharge' => 0];
            $where = ['quote_id = ?' => $quoteId];

            // Execute the update query
            $connection->update($tableName, $data, $where);
        } catch (\Exception $e) {
            $this->logger->error('Error resetting discount overcharge', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear discount and remove admin_note from remarks in amasty_quote table
     * (mirror of working admin-side logic in Delete controller)
     *
     * @param int $quoteId
     * @return void
     */
    protected function clearDiscountAndRemarks(int $quoteId): void
    {
        try {
            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getTableName('amasty_quote');

            // Get current remarks
            $select = $connection->select()
                ->from($tableName, ['remarks'])
                ->where('quote_id = ?', $quoteId)
                ->limit(1);

            $remarksJson = $connection->fetchOne($select);

            // Prepare update data: reset discount (and surcharge if present)
            $data = ['discount' => 0, 'surcharge' => 0];

            // Clear admin_note from remarks if it exists (flat JSON like admin side)
            if ($remarksJson) {
                $remarks = json_decode($remarksJson, true);
                if (is_array($remarks) && isset($remarks['admin_note'])) {
                    unset($remarks['admin_note']);

                    // If remarks is now empty or only has an empty customer_note, set to null
                    if (
                        empty($remarks)
                        || (
                            count($remarks) === 1
                            && isset($remarks['customer_note'])
                            && empty($remarks['customer_note'])
                        )
                    ) {
                        $data['remarks'] = null;
                    } else {
                        $data['remarks'] = json_encode($remarks);
                    }
                }
            }

            // Update amasty_quote table
            $where = ['quote_id = ?' => $quoteId];
            $connection->update($tableName, $data, $where);

        } catch (\Exception $e) {
            $this->logger->error('clearDiscountAndRemarks error', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
