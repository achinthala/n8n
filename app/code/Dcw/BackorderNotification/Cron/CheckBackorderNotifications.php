<?php
namespace Dcw\BackorderNotification\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as OrderItemResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Dcw\BackorderNotification\Service\KlaviyoNotificationService;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class CheckBackorderNotifications
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
     * @var Configurable
     */
    protected $configurableProductType;

    /**
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param KlaviyoNotificationService $klaviyoNotificationService
     * @param OrderItemResource $orderItemResource
     * @param Configurable $configurableProductType
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ProductRepositoryInterface $productRepository,
        KlaviyoNotificationService $klaviyoNotificationService,
        OrderItemResource $orderItemResource,
        Configurable $configurableProductType
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productRepository = $productRepository;
        $this->klaviyoNotificationService = $klaviyoNotificationService;
        $this->orderItemResource = $orderItemResource;
        $this->configurableProductType = $configurableProductType;
    }

    /**
     * Execute cron job - check orders every 5 minutes
     *
     * @return void
     */
    public function execute()
    {
        // Check if module is enabled
        if (!$this->klaviyoNotificationService->isEnabled()) {
            return;
        }

        try {
            // Get orders that haven't been processed yet
            $orders = $this->getOrdersToProcess();

            if ($orders->getSize() === 0) {
                return;
            }

            foreach ($orders as $order) {
                try {
                    $this->processOrder($order);
                } catch (\Exception $e) {
                    $this->klaviyoNotificationService->getCustomLogger()->err('BackorderNotification: Error processing order - Order ID: ' . $order->getId() . ' | Increment ID: ' . $order->getIncrementId() . ' | Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                    // Continue processing other orders
                    continue;
                }
            }

        } catch (\Exception $e) {
            $this->klaviyoNotificationService->getCustomLogger()->err('BackorderNotification: Cron job failed - Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Get orders that need to be processed
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function getOrdersToProcess()
    {
        $collection = $this->orderCollectionFactory->create();
        
        // Get orders where flag is NULL or '0'
        $collection->addFieldToFilter('backorder_notification_sent', [
                ['null' => true],
                ['eq' => '0']
            ])
            // Exclude complete, closed, canceled, and holded orders
            ->addFieldToFilter('state', ['nin' => ['complete', 'closed', 'canceled', 'holded']])
            ->setOrder('created_at', 'ASC')
            ->setPageSize(10); // Process 10 orders per run

        return $collection;
    }

    /**
     * Process individual order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    protected function processOrder($order)
    {
        $shouldSetFlag = false;
        $orderId = $order->getId();
        $incrementId = $order->getIncrementId();

        // Check each order item
        foreach ($order->getAllVisibleItems() as $orderItem) {
            if ($this->checkItemQualifies($orderItem)) {
                $shouldSetFlag = true;
                
                // Get promise date from product and current promise date from order item
                $productPromiseDate = $this->getProductPromiseDate($orderItem);
                
                // Get current promise date from pdp_line_item
                $currentPromiseDate = $this->klaviyoNotificationService->getCurrentPromiseDateFromItem($orderItem);
                
                if ($productPromiseDate) {
                    // Update pdp_line_item if promise date is different or empty (trim to handle whitespace)
                    $trimmedProductDate = trim($productPromiseDate);
                    $trimmedCurrentDate = $currentPromiseDate ? trim($currentPromiseDate) : '';
                    if ($trimmedProductDate !== $trimmedCurrentDate) {
                        $this->updatePdpLineItemWithPromiseDate($orderItem, $productPromiseDate);
                        $this->klaviyoNotificationService->triggerBackorderNotificationToKlaviyo($orderItem, $productPromiseDate, $currentPromiseDate);
                    }
                }
            }
        }

        // Set flag based on check
        $flagValue = $shouldSetFlag ? '1' : '2';
        //$order->setData('backorder_notification_sent', $flagValue);
        //$order->save();
    }

    /**
     * Check if order item qualifies for backorder notification
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return bool
     */
    protected function checkItemQualifies($orderItem)
    {
        // Get pdp_line_item JSON data
        $pdpLineItemJson = $orderItem->getData('pdp_line_item');

        if (!$pdpLineItemJson) {
            return false;
        }

        // Parse JSON
        $pdpData = json_decode($pdpLineItemJson, true);

        if (!is_array($pdpData)) {
            return false;
        }

        // Check if promise_date is empty or different from product's promise date
        $currentPromiseDate = $pdpData['promise_date'] ?? '';

        // Check if product has PIM promise date
        $hasProductPromiseDate = $this->hasProductPromiseDate($orderItem);
        
        if (!$hasProductPromiseDate) {
            return false;
        }

        // Get product promise date to compare
        $productPromiseDate = $this->getProductPromiseDate($orderItem);
        
        // Qualify if promise_date is empty OR if it's different from product's promise date (trim to handle whitespace)
        $trimmedCurrentDate = $currentPromiseDate ? trim($currentPromiseDate) : '';
        $trimmedProductDate = $productPromiseDate ? trim($productPromiseDate) : '';
        return empty($trimmedCurrentDate) || ($trimmedProductDate !== $trimmedCurrentDate);
    }

    /**
     * Check if product has PIM promise date
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return bool
     */
    protected function hasProductPromiseDate($orderItem)
    {
        try {
            // Get product
            $product = $orderItem->getProduct();

            if ($product && $product->getId()) {
                $product = $this->productRepository->get($orderItem->getSku());
            }

            // Get PIM promise date
            $promiseDate = $product->getData('incstores_pim_promise_date');

            // Return true if date exists and is not empty
            return !empty($promiseDate);

        } catch (NoSuchEntityException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get product promise date
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return string|null
     */
    protected function getProductPromiseDate($orderItem)
    {
        try {
            $sku = $orderItem->getSku();
            
            // Always reload product from repository to ensure we have fresh data
            $product = $this->productRepository->get($sku);
            
            $promiseDate = $product->getData('incstores_pim_promise_date');
            
            return !empty($promiseDate) ? $promiseDate : null;

        } catch (\Exception $e) {
            $this->getCustomLogger()->err('BackorderNotification: Error getting product promise date - Item ID: ' . $orderItem->getId() . ' | SKU: ' . $orderItem->getSku() . ' | Error: ' . $e->getMessage());
            return null;
        }
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
                $this->klaviyoNotificationService->getCustomLogger()->err('BackorderNotification: JSON encoding verification failed - Item ID: ' . $itemId . ' | SKU: ' . $item->getSku() . ' | Promise Date: ' . $promiseDate);
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
                $this->klaviyoNotificationService->getCustomLogger()->err('BackorderNotification: Promise date was not saved correctly - Item ID: ' . $itemId . ' | SKU: ' . $item->getSku() . ' | Expected: ' . $promiseDate . ' | Actual: ' . $savedPromiseDate);
            }

        } catch (\Exception $e) {
            $this->klaviyoNotificationService->getCustomLogger()->err('BackorderNotification: Error updating pdp_line_item - Item ID: ' . $item->getId() . ' | SKU: ' . $item->getSku() . ' | Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        }
    }
}

