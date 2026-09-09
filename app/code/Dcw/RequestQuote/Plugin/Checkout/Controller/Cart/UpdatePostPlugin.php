<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Plugin\Checkout\Controller\Cart;

use Dcw\RequestQuote\Service\DiscountThresholdService;
use Magento\Checkout\Controller\Cart\UpdatePost as CartUpdatePost;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Plugin to validate discount threshold when updating cart items from moved quotes
 */
class UpdatePostPlugin
{
    /**
     * @var DiscountThresholdService
     */
    protected $discountThresholdService;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param DiscountThresholdService $discountThresholdService
     * @param ResourceConnection $resourceConnection
     * @param Cart $cart
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        DiscountThresholdService $discountThresholdService,
        ResourceConnection $resourceConnection,
        Cart $cart,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager
    ) {
        $this->discountThresholdService = $discountThresholdService;
        $this->resourceConnection = $resourceConnection;
        $this->cart = $cart;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Validate threshold before updating cart items
     *
     * @param CartUpdatePost $subject
     * @param callable $proceed
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundExecute(CartUpdatePost $subject, callable $proceed)
    {
        $cartData = $subject->getRequest()->getParam('cart');

        if (is_array($cartData) && !empty($cartData)) {
            try {
                $quote = $this->cart->getQuote();
                
                // Group items by Amasty quote ID
                $itemsByQuoteId = [];
                
                foreach ($quote->getAllItems() as $item) {
                    if ($item->getParentItemId()) {
                        continue;
                    }
                    
                    $itemId = (int)$item->getId();
                    $amastyQuoteId = $this->getAmastyQuoteIdFromItem($itemId);
                    
                    if ($amastyQuoteId) {
                        if (!isset($itemsByQuoteId[$amastyQuoteId])) {
                            $itemsByQuoteId[$amastyQuoteId] = [];
                        }
                        
                        // Get updated qty from cartData or use current qty
                        $updatedQty = isset($cartData[$itemId]['qty']) 
                            ? (float)trim($cartData[$itemId]['qty']) 
                            : (float)$item->getQty();
                        
                        // Only include items with qty > 0
                        if ($updatedQty > 0) {
                            $itemsByQuoteId[$amastyQuoteId][$itemId] = [
                                'item' => $item,
                                'qty' => $updatedQty,
                                'amasty_quote_id' => $amastyQuoteId
                            ];
                        }
                    }
                }
                
                // Validate each quote group
                foreach ($itemsByQuoteId as $amastyQuoteId => $items) {
                    // Check if quote has discount
                    $hasDiscount = $this->discountThresholdService->hasDiscount($amastyQuoteId);
                    
                    if ($hasDiscount) {
                        // Get original quote
                        $amastyQuote = $this->getAmastyQuote($amastyQuoteId);
                        if ($amastyQuote) {
                            // Build current items array for validation
                            $currentItems = [];
                            
                            foreach ($items as $cartItemId => $itemData) {
                                $item = $itemData['item'];
                                $itemQty = $itemData['qty'];
                                
                                // Get original item ID from Amasty quote (by matching SKU)
                                $originalItemId = $this->getOriginalItemIdFromAmastyQuote($amastyQuote, $item->getSku());
                                
                                if ($originalItemId) {
                                    // Get original_qty_when_discount_applied from original quote item
                                    $originalQty = $this->discountThresholdService->getOriginalQtyFromDatabase($originalItemId);
                                    
                                    if ($originalQty > 0 && $itemQty > 0) {
                                        $currentItems[$originalItemId] = [
                                            'qty' => $itemQty,
                                            'row_total' => (float)$item->getRowTotal(),
                                            'original_qty' => $originalQty
                                        ];
                                    }
                                }
                            }
                            
                            // Validate changes (pass cart item IDs and magento quote id for correct removed-items check after merge)
                            $cartItemIds = array_keys($items);
                            $magentoQuoteId = (int)$quote->getId();
                            $validation = $this->discountThresholdService->validateChanges(
                                $amastyQuoteId,
                                $currentItems,
                                $cartItemIds,
                                $magentoQuoteId
                            );
                            
                            if (!$validation['allowed']) {
                                // If validation suggests removing discount, support popup for AJAX calls
                                if (!empty($validation['remove_discount'])) {
                                    $request = $subject->getRequest();
                                    $isAjax = $request->isXmlHttpRequest()
                                        || $request->getHeader('X-Requested-With') === 'XMLHttpRequest';
                                    
                                    if ($isAjax) {
                                        /** @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory */
                                        $resultJsonFactory = \Magento\Framework\App\ObjectManager::getInstance()
                                            ->get(\Magento\Framework\Controller\Result\JsonFactory::class);
                                        
                                        $resultJson = $resultJsonFactory->create();
                                        return $resultJson->setData([
                                            'show_discount_removal_popup' => true,
                                            'message' => $validation['popup_message']
                                                ?? __('Your discount will be removed if you proceed with these changes. Do you want to continue?'),
                                            'quote_id' => $amastyQuoteId
                                        ]);
                                    }
                                }

                                // Fallback: old behaviour – add error and redirect to cart
                                $this->messageManager->addErrorMessage($validation['message']);
                                $resultRedirect = $this->resultRedirectFactory->create();
                                $resultRedirect->setPath('checkout/cart');
                                return $resultRedirect;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue if validation fails - don't block cart updates
            }
        }

        return $proceed();
    }

    /**
     * Get Amasty quote ID from cart item
     *
     * @param int $itemId
     * @return int|null
     */
    protected function getAmastyQuoteIdFromItem(int $itemId): ?int
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $query = $connection->select()
                ->from($connection->getTableName("quote_item_option"), ['value'])
                ->where("item_id = ?", $itemId)
                ->where("code = ?", 'amasty_quote_id')
                ->limit(1);
            
            $result = $connection->fetchOne($query);
            
            return $result ? (int)$result : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get Amasty quote by ID
     *
     * @param int $quoteId
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    protected function getAmastyQuote(int $quoteId)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $quoteRepository = $objectManager->get(\Amasty\RequestQuote\Api\QuoteRepositoryInterface::class);
            return $quoteRepository->get($quoteId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get original item ID from Amasty quote by SKU
     *
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $amastyQuote
     * @param string $sku
     * @return int|null
     */
    protected function getOriginalItemIdFromAmastyQuote($amastyQuote, string $sku): ?int
    {
        try {
            foreach ($amastyQuote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                
                if ($item->getSku() == $sku) {
                    return (int)$item->getId();
                }
            }
        } catch (\Exception $e) {
            // Continue
        }
        
        return null;
    }
}
