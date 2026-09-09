<?php
/**
 * Discount Threshold Service
 * Validates if customer changes to quote exceed the configured threshold
 */

namespace Dcw\RequestQuote\Service;

use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class DiscountThresholdService
{
    /**
     * Configuration path
     */
    const XML_PATH_THRESHOLD_PERCENTAGE = 'requestquote/discount_threshold/threshold_percentage';

    /**
     * Default threshold
     */
    const DEFAULT_THRESHOLD = 20;

    /**
     * @var QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param QuoteRepositoryInterface $quoteRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteRepositoryInterface $quoteRepository,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Get ResourceConnection instance
     *
     * @return ResourceConnection
     */
    public function getResourceConnection(): ResourceConnection
    {
        return $this->resourceConnection;
    }

    /**
     * Get threshold percentage from configuration
     *
     * @param int|null $storeId
     * @return float
     */
    public function getThresholdPercentage($storeId = null): float
    {
        $threshold = (float) $this->scopeConfig->getValue(
            self::XML_PATH_THRESHOLD_PERCENTAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        return $threshold > 0 ? $threshold : self::DEFAULT_THRESHOLD;
    }

    /**
     * Check if quote has discount
     * Checks the amasty_quote table directly (not the regular quote table)
     *
     * @param int $quoteId
     * @return bool
     */
    public function hasDiscount($quoteId): bool
    {
        try {
            // Check discount from amasty_quote table (where Amasty stores it)
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('amasty_quote');
            
            $select = $connection->select()
                ->from($tableName, ['discount'])
                ->where('quote_id = ?', $quoteId)
                ->limit(1);
            
            $discount = $connection->fetchOne($select);
            
            // Check if discount exists and is greater than 0
            return $discount !== false && abs((float)$discount) > 0.0001;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save original quantity to each quote item when discount is first applied
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     */
    public function saveOriginalQtyToItems($quote): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $itemTable = $this->resourceConnection->getTableName('quote_item');
            
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue; // Skip child items
                }
                
                $itemId = (int)$item->getId();
                if (!$itemId) {
                    continue;
                }
                
                // Only save if original_qty_when_discount_applied is not already set
                // This prevents overwriting if discount was already applied before
                $existingQty = $connection->fetchOne(
                    $connection->select()
                        ->from($itemTable, ['original_qty_when_discount_applied'])
                        ->where('item_id = ?', $itemId)
                        ->limit(1)
                );
                
                if ($existingQty === false || $existingQty === null) {
                    // Save original quantity to this item
                    $data = ['original_qty_when_discount_applied' => (float)$item->getQty()];
                    $where = ['item_id = ?' => $itemId];
                    $connection->update($itemTable, $data, $where);
                }
            }
        } catch (\Exception $e) {
            // Continue silently - failure shouldn't block quote operations
        }
    }

    /**
     * Update original quantity to current quantity for all quote items (when discount is changed)
     * This is used when discount is changed from one value to another (e.g., 10% to 20%)
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     */
    public function updateOriginalQtyToItems($quote): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $itemTable = $this->resourceConnection->getTableName('quote_item');
            
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue; // Skip child items
                }
                
                $itemId = (int)$item->getId();
                if (!$itemId) {
                    continue;
                }
                
                // Always update to current quantity when discount is changed
                $data = ['original_qty_when_discount_applied' => (float)$item->getQty()];
                $where = ['item_id = ?' => $itemId];
                $connection->update($itemTable, $data, $where);
            }
        } catch (\Exception $e) {
            // Continue silently - failure shouldn't block quote operations
        }
    }

    /**
     * Clear original quantity from all quote items (when discount is removed)
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     */
    public function clearOriginalQtyFromItems($quote): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $itemTable = $this->resourceConnection->getTableName('quote_item');
            
            $itemIds = [];
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue; // Skip child items
                }
                
                $itemId = (int)$item->getId();
                if ($itemId) {
                    $itemIds[] = $itemId;
                }
            }
            
            if (!empty($itemIds)) {
                $data = ['original_qty_when_discount_applied' => null];
                $where = ['item_id IN (?)' => $itemIds];
                $connection->update($itemTable, $data, $where);
            }
        } catch (\Exception $e) {
            // Continue silently
        }
    }

    /**
     * Get original quantity from database for a quote item
     *
     * @param int $itemId
     * @return float
     */
    public function getOriginalQtyFromDatabase(int $itemId): float
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $itemTable = $this->resourceConnection->getTableName('quote_item');
            
            $originalQty = $connection->fetchOne(
                $connection->select()
                    ->from($itemTable, ['original_qty_when_discount_applied'])
                    ->where('item_id = ?', $itemId)
                    ->limit(1)
            );
            
            return $originalQty !== false && $originalQty !== null ? (float)$originalQty : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Validate if customer changes exceed threshold
     * Compares against original_qty_when_discount_applied stored at line item level
     *
     * @param int $quoteId Amasty quote ID (used for hasDiscount; for removed-items query see $magentoQuoteId)
     * @param array $currentItems Array of current items [item_id => ['qty' => X, 'row_total' => Y, 'original_qty' => Z]]
     * @param array|null $excludeItemIds Item IDs to exclude from "removed" check (e.g. cart item IDs when quote merged to cart)
     * @param int|null $magentoQuoteId Magento quote entity_id for quote_item query (when items are in cart after merge)
     * @return array ['allowed' => bool, 'reduction_percentage' => float, 'message' => string]
     * @throws LocalizedException
     */
    public function validateChanges($quoteId, array $currentItems, ?array $excludeItemIds = null, ?int $magentoQuoteId = null): array
    {
        // If quote doesn't have discount, allow all changes
        if (!$this->hasDiscount($quoteId)) {
            return [
                'allowed' => true,
                'reduction_percentage' => 0,
                'message' => ''
            ];
        }

        $threshold = $this->getThresholdPercentage();
        
        // Calculate original total quantity from original_qty_when_discount_applied stored in items
        $originalTotalQty = 0;
        $currentTotalQty = 0;
        $removedItems = [];
        
        // Get original quantities from currentItems array (passed from controller)
        // The controller should include 'original_qty' in each item's data
        foreach ($currentItems as $itemId => $currentItem) {
            $originalQty = (float)($currentItem['original_qty'] ?? 0);
            $currentQty = (float)($currentItem['qty'] ?? 0);
            
            if ($originalQty > 0) {
                // This item had original qty when discount was applied
                $originalTotalQty += $originalQty;
                $currentTotalQty += $currentQty;
            } else {
                // This is a new item added after discount was applied
                // Only count current qty (not in original calculation)
                $currentTotalQty += $currentQty;
            }
        }

        // If no items have original_qty set, allow changes (discount might have been applied before this feature)
        if ($originalTotalQty <= 0) {
            return [
                'allowed' => true,
                'reduction_percentage' => 0,
                'message' => ''
            ];
        }

        // Check for removed items - if an item had original_qty but is not in currentItems
        // When quote is merged to cart, items have cart IDs; currentItems may be keyed by Amasty IDs.
        // Use $excludeItemIds (cart item IDs) and $magentoQuoteId (cart quote) when provided.
        try {
            $connection = $this->resourceConnection->getConnection();
            $itemTable = $this->resourceConnection->getTableName('quote_item');
            
            $idsToExclude = $excludeItemIds ?? array_keys($currentItems);
            $quoteIdForQuery = $magentoQuoteId ?? $quoteId;
            
            // Build query: find items with original_qty that are NOT in our kept-items list (removed items)
            $removedItemsQuery = $connection->select()
                ->from($itemTable, ['item_id', 'original_qty_when_discount_applied'])
                ->where('quote_id = ?', $quoteIdForQuery)
                ->where('original_qty_when_discount_applied IS NOT NULL')
                ->where('original_qty_when_discount_applied > ?', 0);
            
            if (!empty($idsToExclude)) {
                $removedItemsQuery->where('item_id NOT IN (?)', $idsToExclude);
            }
            
            $removedItems = $connection->fetchAll($removedItemsQuery);
            
            if (!empty($removedItems)) {
                // Items with original_qty were removed - this is 100% reduction
                return [
                    'allowed' => false,
                    'reduction_percentage' => 100,
                    'message' => __('Discount quantity reduction threshold over the limit. You cannot make changes on this quote. Please contact the admin.'),
                    'remove_discount' => true,
                    'popup_message' => __('Your discount will be removed if you proceed with these changes. Do you want to continue?')
                ];
            }
        } catch (\Exception $e) {
            // Continue if query fails - don't block validation
        }

        // Only check threshold if current qty is LESS than original (reduction)
        // If current qty is equal or greater than original, allow it (no threshold check for increases)
        if ($currentTotalQty >= $originalTotalQty) {
            // Current quantity is same or more than original - no reduction, allow it
            return [
                'allowed' => true,
                'reduction_percentage' => 0,
                'message' => ''
            ];
        }

        // Calculate reduction percentage from original quantity (only when reducing)
        $reductionQty = $originalTotalQty - $currentTotalQty;
        $reductionPercentage = ($reductionQty / $originalTotalQty) * 100;

        // Check if reduction exceeds threshold
        if ($reductionPercentage >= $threshold) {
            return [
                'allowed' => false,
                'reduction_percentage' => $reductionPercentage,
                'message' => __('Discount quantity reduction threshold over the limit. You cannot make changes on this quote. Please contact the admin.'),
                'remove_discount' => true,
                'popup_message' => __('You have reduced the quantity of items in a discounted quote beyond the allowed limit. Your discount will be removed if you proceed. Do you want to continue?')
            ];
        }

        return [
            'allowed' => true,
            'reduction_percentage' => $reductionPercentage,
            'message' => ''
        ];
    }

}

