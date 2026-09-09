<?php
namespace Dcw\BackorderNotification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as OrderItemResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Dcw\BackorderNotification\Service\KlaviyoNotificationService;
use Magento\Framework\App\ResourceConnection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var KlaviyoNotificationService
     */
    protected $klaviyoNotificationService;

    /**
     * @var OrderItemResource
     */
    protected $orderItemResource;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Configurable
     */
    protected $configurableProductType;

    /**
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param KlaviyoNotificationService $klaviyoNotificationService
     * @param OrderItemResource $orderItemResource
     * @param ResourceConnection $resourceConnection
     * @param Configurable $configurableProductType
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ProductRepositoryInterface $productRepository,
        KlaviyoNotificationService $klaviyoNotificationService,
        OrderItemResource $orderItemResource,
        ResourceConnection $resourceConnection,
        Configurable $configurableProductType
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productRepository = $productRepository;
        $this->klaviyoNotificationService = $klaviyoNotificationService;
        $this->orderItemResource = $orderItemResource;
        $this->resourceConnection = $resourceConnection;
        $this->configurableProductType = $configurableProductType;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Check if module is enabled
        if (!$this->klaviyoNotificationService->isEnabled()) {
            return;
        }

        try {
            /** @var Product $product */
            $product = $observer->getEvent()->getProduct();
            
            if (!$product || !$product->getId()) {
                return;
            }

            // Get the new promise date
            $newPromiseDate = $product->getData('incstores_pim_promise_date');
            
            // Get the original promise date from the product before save
            $originalProduct = $product->getOrigData('incstores_pim_promise_date');
            
            // Only process if promise date has changed
            if ($newPromiseDate === $originalProduct) {
                return;
            }

            // If new promise date is empty, don't process
            if (empty($newPromiseDate)) {
                return;
            }

            // Process orders containing this product
            $this->processOrdersForProduct($product, $newPromiseDate);

        } catch (\Exception $e) {
            $this->getCustomLogger()->err('BackorderNotification: Error in ProductSaveAfter observer - ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Process orders containing the product
     *
     * @param Product $product
     * @param string $promiseDate
     * @return void
     */
    protected function processOrdersForProduct($product, $promiseDate)
    {
        $productId = $product->getId();
        
        // Get parent product IDs if this is a simple product
        $productIdsToCheck = [$productId];
        if ($product->getTypeId() === 'simple') {
            $parentIds = $this->configurableProductType->getParentIdsByChild($productId);
            if (!empty($parentIds)) {
                $productIdsToCheck = array_merge($productIdsToCheck, $parentIds);
            }
        }

        // Query order items directly by product ID
        $connection = $this->resourceConnection->getConnection();
        $orderItemTable = $this->resourceConnection->getTableName('sales_order_item');
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        
        // Get order item IDs that match this product
        $select = $connection->select()
            ->from(['oi' => $orderItemTable], ['item_id', 'order_id', 'product_id'])
            ->join(
                ['o' => $orderTable],
                'oi.order_id = o.entity_id',
                []
            )
            ->where('oi.product_id IN(?)', $productIdsToCheck)
            ->where('o.backorder_notification_sent IS NULL OR o.backorder_notification_sent = ?', '0')
            ->where('o.state NOT IN(?)', ['complete', 'closed', 'canceled', 'holded']);
        
        $orderItems = $connection->fetchAll($select);
        
        if (empty($orderItems)) {
            return;
        }

        // Group by order ID to process efficiently
        $ordersToProcess = [];
        foreach ($orderItems as $itemData) {
            $orderId = $itemData['order_id'];
            if (!isset($ordersToProcess[$orderId])) {
                $ordersToProcess[$orderId] = [];
            }
            $ordersToProcess[$orderId][] = $itemData['item_id'];
        }

        // Process each order
        foreach ($ordersToProcess as $orderId => $itemIds) {
            try {
                $order = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('entity_id', $orderId)
                    ->getFirstItem();
                
                if (!$order || !$order->getId()) {
                    continue;
                }

                $shouldUpdateOrder = false;
                
                foreach ($order->getAllVisibleItems() as $orderItem) {
                    if (in_array($orderItem->getId(), $itemIds)) {
                    // Get current promise date from pdp_line_item
                    $currentPromiseDate = $this->klaviyoNotificationService->getCurrentPromiseDateFromItem($orderItem);
                        
                        // Only update if promise date is different (trim to handle whitespace)
                        $trimmedPromiseDate = $promiseDate ? trim($promiseDate) : '';
                        $trimmedCurrentDate = $currentPromiseDate ? trim($currentPromiseDate) : '';
                        if ($trimmedPromiseDate !== $trimmedCurrentDate) {
                            $this->updatePdpLineItemWithPromiseDate($orderItem, $promiseDate);
                            $this->klaviyoNotificationService->triggerBackorderNotificationToKlaviyo($orderItem, $promiseDate, $currentPromiseDate);
                            $shouldUpdateOrder = true;
                        }
                    }
                }
                
                // Mark order as processed if any items were updated
                if ($shouldUpdateOrder) {
                    //$order->setData('backorder_notification_sent', '1');
                    //$order->save();
                }
            } catch (\Exception $e) {
                $this->getCustomLogger()->err('BackorderNotification: Error processing order in observer - Order ID: ' . $orderId . ' | Error: ' . $e->getMessage());
            }
        }
    }


    /**
     * Get custom logger instance for BackOrderNotification.log
     *
     * @return \Zend_Log
     */
    protected function getCustomLogger()
    {
        return $this->klaviyoNotificationService->getCustomLogger();
    }

    /**
     * Update pdp_line_item JSON with promise_date
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param string $promiseDate
     * @return void
     */
    protected function updatePdpLineItemWithPromiseDate($orderItem, $promiseDate)
    {
        try {
            // Update parent/configurable item
            $this->updateItemPdpLineItem($orderItem, $promiseDate);

            // Also update child item if this is a configurable product
            if ($orderItem->getProductType() === 'configurable') {
                $childItems = $orderItem->getChildrenItems();
                if ($childItems) {
                    foreach ($childItems as $childItem) {
                        $this->updateItemPdpLineItem($childItem, $promiseDate);
                    }
                }
            }

        } catch (\Exception $e) {
            // Silent fail - don't break the process
        }
    }

    /**
     * Update individual item's pdp_line_item JSON
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param string $promiseDate
     * @return void
     */
    protected function updateItemPdpLineItem($item, $promiseDate)
    {
        try {
            $itemId = $item->getId();
            
            // Get fresh data from database to ensure we have the latest pdp_line_item
            $pdpLineItemJson = $item->getData('pdp_line_item');
            
            if (!$pdpLineItemJson) {
                return;
            }

            // Parse existing JSON
            $pdpData = json_decode($pdpLineItemJson, true);
            
            if (!is_array($pdpData)) {
                return;
            }

            // Add promise_date to the JSON
            $pdpData['promise_date'] = $promiseDate;
            $updatedJson = json_encode($pdpData, JSON_UNESCAPED_SLASHES);

            // Verify the JSON encoding worked
            $verifyData = json_decode($updatedJson, true);
            if (!isset($verifyData['promise_date']) || $verifyData['promise_date'] !== $promiseDate) {
                $this->getCustomLogger()->err('BackorderNotification: JSON encoding verification failed - Item ID: ' . $itemId . ' | SKU: ' . $item->getSku() . ' | Promise Date: ' . $promiseDate);
                return;
            }

            // Save updated JSON back to item using resource model to ensure persistence
            $item->setData('pdp_line_item', $updatedJson);
            $this->orderItemResource->save($item);

            // Reload item from database to verify the save worked
            $this->orderItemResource->load($item, $itemId);
            $savedPdpLineItem = $item->getData('pdp_line_item');
            $savedData = json_decode($savedPdpLineItem, true);
            $savedPromiseDate = $savedData['promise_date'] ?? '';

            if ($savedPromiseDate !== $promiseDate) {
                $this->getCustomLogger()->err('BackorderNotification: Promise date was not saved correctly - Item ID: ' . $itemId . ' | SKU: ' . $item->getSku() . ' | Expected: ' . $promiseDate . ' | Actual: ' . $savedPromiseDate);
            }

        } catch (\Exception $e) {
            $this->getCustomLogger()->err('BackorderNotification: Error updating pdp_line_item - Item ID: ' . $item->getId() . ' | SKU: ' . $item->getSku() . ' | Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        }
    }
}

