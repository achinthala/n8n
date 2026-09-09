<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Plugin\Adminhtml\Quote\Edit;

use Amasty\RequestQuote\Controller\Adminhtml\Quote\Edit\Save;
use Amasty\RequestQuote\Model\QuoteRepository;
use Dcw\RequestQuote\Service\QuoteChangeLogger;
use Dcw\RequestQuote\Service\AdminQuotePermissionService;
use Dcw\RequestQuote\Service\QuoteLockService;
use Dcw\RequestQuote\Service\DiscountThresholdService;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResourceConnection;
use Amasty\RequestQuote\Helper\Data as AmastyDataHelper;
use Amasty\RequestQuote\Helper\Date as AmastyDateHelper;
use Magento\Framework\Session\SessionManagerInterface;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;

/**
 * Plugin to log admin changes to quotes and validate permissions
 */
class SavePlugin
{
    /**
     * @param QuoteRepository $quoteRepository
     * @param QuoteChangeLogger $changeLogger
     * @param AdminQuotePermissionService $permissionService
     * @param QuoteLockService $quoteLockService
     * @param DiscountThresholdService $discountThresholdService
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param ResourceConnection $resourceConnection
     * @param AmastyDataHelper $amastyDataHelper
     * @param AmastyDateHelper $amastyDateHelper
     */
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteChangeLogger $changeLogger,
        private readonly AdminQuotePermissionService $permissionService,
        private readonly QuoteLockService $quoteLockService,
        private readonly DiscountThresholdService $discountThresholdService,
        private readonly RequestInterface $request,
        private readonly ManagerInterface $messageManager,
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly AmastyDataHelper $amastyDataHelper,
        private readonly AmastyDateHelper $amastyDateHelper,
        private readonly SessionManagerInterface $sessionManager,
        private readonly RequestQuoteViewModel $requestQuoteViewModel
    ) {
    }

    /**
     * Store original quote data before save
     *
     * @var array
     */
    private array $originalQuoteData = [];

    /**
     * Handle permission validation and redirect on failure
     *
     * @param Save $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(Save $subject, callable $proceed)
    {
        $quoteId = (int)$this->request->getParam('quote_id');
        
        // Validate permissions before proceeding
        if ($quoteId) {
            try {
                // Check if quote is locked by customer
                $lock = $this->quoteLockService->getActiveLock($quoteId);
                if ($lock && $lock->getLockedByType() === 'customer') {
                    $customerName = $lock->getLockedByName() ?: __('Customer');
                    throw new LocalizedException(
                        __('This quote is currently being edited by customer %1. Please wait until they finish editing or use "Force Unlock" to override.', $customerName)
                    );
                }
                
                // Store original quote data before save
                $quote = $this->quoteRepository->get($quoteId);
                $quote->getItemsCollection()->load();
                
                $this->originalQuoteData[$quoteId] = [
                    'items' => [],
                    'discount' => $quote->getDiscount() ?? 0,
                    'status' => $quote->getStatus(),
                    'admin_user_assistance' => $quote->getAdminUserAssistance(),
                    'custom_fee' => $quote->getCustomFee() ?? 0,
                ];
                
                // Store original item data by SKU for reliable matching
                foreach ($quote->getAllItems() as $item) {
                    if ($item->getParentItemId()) {
                        continue; // Skip child items
                    }
                    $sku = $item->getSku();
                    $this->originalQuoteData[$quoteId]['items'][$sku] = [
                        'item_id' => $item->getId(),
                        'sku' => $sku,
                        'name' => $item->getName(),
                        'qty' => $item->getQty(),
                        'price' => $item->getPrice(),
                        'row_total' => $item->getRowTotal(),
                    ];
                }

                // Validate permissions based on request data
                $this->validatePermissions($quoteId);
            } catch (LocalizedException $e) {
                // Permission validation failed - add message and redirect back to edit page
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('amasty_quote/quote/edit', ['quote_id' => $quoteId]);
                return $resultRedirect;
            } catch (\Exception $e) {
                // Continue silently - permission validation errors are handled above
            }
        }
        
        // If validation passed, proceed with save
        try {
            return $proceed();
        } catch (LocalizedException $e) {
            // Catch any LocalizedExceptions from the controller execution
            // Add message and redirect back to edit page
            $quoteId = (int)$this->request->getParam('quote_id');
            if ($quoteId) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('amasty_quote/quote/edit', ['quote_id' => $quoteId]);
                return $resultRedirect;
            }
            // Re-throw if no quote ID
            throw $e;
        }
    }

    /**
     * Validate admin permissions for quote edits
     *
     * @param int $quoteId
     * @return void
     * @throws LocalizedException
     */
    protected function validatePermissions(int $quoteId): void
    {
        if (!isset($this->originalQuoteData[$quoteId])) {
            return;
        }

        $originalData = $this->originalQuoteData[$quoteId];
        $postData = $this->request->getPostValue();

        // Check for item removals
        $originalItemSkus = array_keys($originalData['items']);
        $postItemSkus = [];
        if (isset($postData['item']) && is_array($postData['item'])) {
            foreach ($postData['item'] as $itemId => $itemData) {
                if (isset($itemData['action']) && $itemData['action'] === 'remove') {
                    if (!$this->permissionService->canRemoveItems()) {
                        throw new LocalizedException(
                            __('You do not have permission to remove items from quotes.')
                        );
                    }
                }
            }
        }

        // Check for quantity changes
        if (isset($postData['item']) && is_array($postData['item'])) {
            foreach ($postData['item'] as $itemId => $itemData) {
                if (isset($itemData['qty'])) {
                    // Find original item by matching item ID or SKU
                    foreach ($originalData['items'] as $originalItem) {
                        if ($originalItem['item_id'] == $itemId || 
                            (isset($itemData['sku']) && $originalItem['sku'] == $itemData['sku'])) {
                            $newQty = (float)$itemData['qty'];
                            $oldQty = (float)$originalItem['qty'];
                            if (abs($newQty - $oldQty) > 0.0001) {
                                if (!$this->permissionService->canEditQuantity()) {
                                    throw new LocalizedException(
                                        __('You do not have permission to edit item quantities.')
                                    );
                                }
                            }
                            break;
                        }
                    }
                }

                // Check for price changes
                if (isset($itemData['price'])) {
                    foreach ($originalData['items'] as $originalItem) {
                        if ($originalItem['item_id'] == $itemId || 
                            (isset($itemData['sku']) && $originalItem['sku'] == $itemData['sku'])) {
                            $newPrice = (float)$itemData['price'];
                            $oldPrice = (float)$originalItem['price'];
                            if (abs($newPrice - $oldPrice) > 0.0001) {
                                if (!$this->permissionService->canEditItemPrices()) {
                                    throw new LocalizedException(
                                        __('You do not have permission to edit item prices.')
                                    );
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Check for discount changes
        if (isset($postData['quote']['discount'])) {
            $newDiscount = (float)$postData['quote']['discount'];
            $oldDiscount = (float)($originalData['discount'] ?? 0);
            
            if (abs($newDiscount - $oldDiscount) > 0.0001) {
                if (!$this->permissionService->canApplyDiscounts()) {
                    throw new LocalizedException(
                        __('You do not have permission to apply discounts.')
                    );
                }

                // Check for 100% discount (zero cart total)
                if ($newDiscount >= 100) {
                    if (!$this->permissionService->canApplyFullDiscount()) {
                        throw new LocalizedException(
                            __('You do not have permission to apply 100% discounts.')
                        );
                    }
                }
            }
        }

        // Check for custom fee (shipping amount) changes
        if (isset($postData['quote']['custom_fee'])) {
            $newCustomFee = (float)$postData['quote']['custom_fee'];
            $oldCustomFee = (float)($originalData['custom_fee'] ?? 0);
            
            if (abs($newCustomFee - $oldCustomFee) > 0.0001) {
                if (!$this->permissionService->canOverrideShipping()) {
                    throw new LocalizedException(
                        __('You do not have permission to modify shipping amounts.')
                    );
                }
            }
        }

        // Check for admin_user_assistance changes
        if (isset($postData['quote']['admin_user_assistance'])) {
            $newAssistance = $postData['quote']['admin_user_assistance'];
            $oldAssistance = $originalData['admin_user_assistance'] ?? '';
            
            if ($newAssistance !== $oldAssistance) {
                if (!$this->permissionService->canEditAdminAssistance()) {
                    throw new LocalizedException(
                        __('You do not have permission to edit Admin User Assistance.')
                    );
                }
            }
        }
    }

    /**
     * Log admin changes after quote save
     *
     * @param Save $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(Save $subject, $result)
    {
        try {
            // Get quote ID from request
            $quoteId = (int)$this->request->getParam('quote_id');
            
            if (!$quoteId) {
                return $result;
            }
            
            if (!isset($this->originalQuoteData[$quoteId])) {
                return $result;
            }

            $originalData = $this->originalQuoteData[$quoteId];
            
            // Get quote after save to compare changes
            $quote = $this->quoteRepository->get($quoteId);
            $quote->getItemsCollection()->load();
            
            // If discount changed, ensure it's applied to items and totals are recalculated
            $postData = $this->request->getPostValue();
            if (isset($postData['quote']['discount'])) {
                $requestedDiscount = (float)$postData['quote']['discount'];
                $currentDiscount = (float)($quote->getDiscount() ?? 0);
                
                // If discount was set (including 100%), apply it to items and recalculate totals
                // Check if discount changed OR if 100% was requested (to ensure it's always applied)
                if (abs($requestedDiscount - $currentDiscount) > 0.01 || $requestedDiscount >= 100) {
                    // Ensure discount is set on quote
                    if (abs($currentDiscount - $requestedDiscount) > 0.01) {
                        $quote->setDiscount($requestedDiscount);
                        $quote->setData('discount', $requestedDiscount);
                        $this->quoteRepository->save($quote);
                    }
                    
                    // Use ViewModel to recalculate prices with discount applied to items
                    $this->requestQuoteViewModel->recalculateQuotePrices($quote, true);
                    
                    // Reload to get updated totals
                    $quote = $this->quoteRepository->get($quoteId);
                    $quote->getItemsCollection()->load();
                }
            }
            
            // Extend expired_date and reminder_date when quote is edited and saved
            $dateUpdated = false;
            if ($expDays = $this->amastyDataHelper->getExpirationTime()) {
                $quote->setExpiredDate($this->amastyDateHelper->increaseDays($expDays));
                $dateUpdated = true;
            }
            
            if ($remDays = $this->amastyDataHelper->getReminderTime()) {
                $quote->setReminderDate($this->amastyDateHelper->increaseDays($remDays));
                $dateUpdated = true;
            }
            
            // Save quote if dates were updated
            if ($dateUpdated) {
                $this->quoteRepository->save($quote);
                // Reload quote to ensure we have the latest data for comparison
                $quote = $this->quoteRepository->get($quoteId);
                $quote->getItemsCollection()->load();
            }

            // Build current items array indexed by SKU for matching
            $currentItems = [];
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue; // Skip child items
                }
                $sku = $item->getSku();
                $currentItems[$sku] = [
                    'item_id' => $item->getId(),
                    'sku' => $sku,
                    'name' => $item->getName(),
                    'qty' => $item->getQty(),
                    'price' => $item->getPrice(),
                    'row_total' => $item->getRowTotal(),
                ];
            }
            
            // Check for removed items (items in original but not in current)
            foreach ($originalData['items'] as $sku => $oldItem) {
                if (!isset($currentItems[$sku])) {
                    // Item was removed
                    $this->changeLogger->logItemRemoved($quoteId, (int)$oldItem['item_id'], $oldItem);
                }
            }
            
            // Check for discount changes
            $oldDiscount = $originalData['discount'] ?? 0;
            $newDiscount = $quote->getDiscount() ?? 0;
            $discountChanged = abs((float)$oldDiscount - (float)$newDiscount) > 0.0001;
            $hasItemUpdates = false;
            
            // Check for updated and added items
            foreach ($currentItems as $sku => $newItem) {
                if (isset($originalData['items'][$sku])) {
                    // Item exists in both - check if it was updated
                    $oldItem = $originalData['items'][$sku];
                    
                    // Check if price or quantity changed (use float comparison for accuracy)
                    $priceChanged = abs((float)$oldItem['price'] - (float)$newItem['price']) > 0.0001;
                    $qtyChanged = abs((float)$oldItem['qty'] - (float)$newItem['qty']) > 0.0001;
                    
                    if ($priceChanged || $qtyChanged) {
                        $hasItemUpdates = true;
                        // Item was updated - ensure oldItem has all required fields
                        $oldItemData = [
                            'item_id' => $oldItem['item_id'],
                            'sku' => $oldItem['sku'],
                            'name' => $oldItem['name'],
                            'qty' => (float)$oldItem['qty'],
                            'price' => (float)$oldItem['price'],
                            'row_total' => (float)$oldItem['row_total'],
                        ];
                        
                        $newItemData = [
                            'item_id' => $newItem['item_id'],
                            'sku' => $newItem['sku'],
                            'name' => $newItem['name'],
                            'qty' => (float)$newItem['qty'],
                            'price' => (float)$newItem['price'],
                            'row_total' => (float)$newItem['row_total'],
                        ];
                        
                        // Include discount in item update log if discount changed
                        if ($discountChanged) {
                            $oldItemData['discount'] = (float)$oldDiscount;
                            $newItemData['discount'] = (float)$newDiscount;
                        }
                        
                        $this->changeLogger->logItemUpdated($quoteId, (int)$newItem['item_id'], $oldItemData, $newItemData);
                    }
                } else {
                    // New item added (not in original data)
                    $newItemData = [
                        'item_id' => $newItem['item_id'],
                        'sku' => $newItem['sku'],
                        'name' => $newItem['name'],
                        'qty' => (float)$newItem['qty'],
                        'price' => (float)$newItem['price'],
                        'row_total' => (float)$newItem['row_total'],
                    ];
                    $this->changeLogger->logItemAdded($quoteId, (int)$newItem['item_id'], $newItemData);
                }
            }

            // Only log discount separately if discount changed but no items were updated
            if ($discountChanged && !$hasItemUpdates) {
                $this->changeLogger->logDiscountChanged(
                    $quoteId,
                    ['discount' => $oldDiscount],
                    ['discount' => $newDiscount]
                );
            }
            
            // Handle discount threshold - store original qty at line item level
            if ($discountChanged) {
                // If discount is being applied for the first time (oldDiscount = 0, newDiscount > 0)
                // OR if discount is being changed (oldDiscount > 0, newDiscount > 0)
                // Save/update original quantity to each quote item
                if ($oldDiscount <= 0.0001 && $newDiscount > 0.0001) {
                    // First time applying discount - save original quantity
                    $this->discountThresholdService->saveOriginalQtyToItems($quote);
                } elseif ($oldDiscount > 0.0001 && $newDiscount > 0.0001) {
                    // Discount is being changed - update original quantity to current quantity
                    $this->discountThresholdService->updateOriginalQtyToItems($quote);
                }
                
                // If discount is being removed (newDiscount = 0), clear original qty from items
                if ($newDiscount <= 0.0001 && $oldDiscount > 0.0001) {
                    $this->discountThresholdService->clearOriginalQtyFromItems($quote);
                }
            }
            
            // If discount is 0, clear discount and remove admin_note from remarks in amasty_quote table
            if ($newDiscount == 0 && ($oldDiscount > 0 || $oldDiscount < 0.0001)) {
                $this->clearDiscountAndRemarks($quoteId);
            }
            
            // Check for custom_fee (shipping amount) changes
            $oldCustomFee = (float)($originalData['custom_fee'] ?? 0);
            $newCustomFee = (float)($quote->getCustomFee() ?? 0);
            
            if (abs($oldCustomFee - $newCustomFee) > 0.0001) {
                $this->changeLogger->logChange(
                    $quoteId,
                    'custom_fee_changed',
                    ['custom_fee' => $oldCustomFee],
                    ['custom_fee' => $newCustomFee]
                );
            }
            
            // Check for status changes
            $oldStatus = $originalData['status'] ?? null;
            $newStatus = $quote->getStatus();
            
            if ($oldStatus !== null && $oldStatus != $newStatus) {
                $this->changeLogger->logStatusChanged(
                    $quoteId,
                    (int)$oldStatus,
                    (int)$newStatus
                );
            }
            
            // Clean up stored data
            unset($this->originalQuoteData[$quoteId]);
            
            // Release lock after successful save
            try {
                $sessionId = $this->sessionManager->getSessionId();
                $this->quoteLockService->releaseLock($quoteId, $sessionId);
            } catch (\Exception $e) {
                // Silent fail - don't break quote save if lock release fails
            }

        } catch (\Exception $e) {
            // Continue silently - don't break admin quote operations
        }

        return $result;
    }

    /**
     * Clear discount and remove admin_note from remarks in amasty_quote table
     *
     * @param int $quoteId
     * @return void
     */
    private function clearDiscountAndRemarks(int $quoteId): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('amasty_quote');
            
            // Get current remarks
            $select = $connection->select()
                ->from($tableName, ['remarks'])
                ->where('quote_id = ?', $quoteId)
                ->limit(1);
            
            $remarksJson = $connection->fetchOne($select);
            
            // Prepare update data
            $data = ['discount' => 0];
            
            // Clear admin_note from remarks if it exists
            if ($remarksJson) {
                $remarks = json_decode($remarksJson, true);
                if (is_array($remarks) && isset($remarks['admin_note'])) {
                    // Remove admin_note from remarks
                    unset($remarks['admin_note']);
                    
                    // If remarks is now empty or only has empty values, set to null
                    if (empty($remarks) || (count($remarks) === 1 && isset($remarks['customer_note']) && empty($remarks['customer_note']))) {
                        $data['remarks'] = null;
                    } else {
                        // Re-encode remarks without admin_note
                        $data['remarks'] = json_encode($remarks);
                    }
                }
            }
            
            // Update amasty_quote table
            $where = ['quote_id = ?' => $quoteId];
            $connection->update($tableName, $data, $where);
            
        } catch (\Exception $e) {
            // Continue silently - quote save should still succeed
        }
    }
}

