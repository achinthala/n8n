<?php
namespace Dcw\BackorderNotification\Plugin\Sales\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class AddBackorderNotificationToApi
{
    /**
     * @var OrderExtensionFactory
     */
    protected $orderExtensionFactory;

    /**
     * @var OrderItemExtensionFactory
     */
    protected $orderItemExtensionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param OrderItemExtensionFactory $orderItemExtensionFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        OrderItemExtensionFactory $orderItemExtensionFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * Add backorder_notification_sent and promiseDate to order API response
     *
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        $this->setBackorderNotificationToExtensionAttributes($order);
        return $order;
    }

    /**
     * Add backorder_notification_sent and promiseDate to order list API response
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function afterGetList(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
    ) {
        $orders = $searchResult->getItems();

        foreach ($orders as $order) {
            $this->setBackorderNotificationToExtensionAttributes($order);
        }

        return $searchResult;
    }

    /**
     * Set backorder notification to extension attributes
     *
     * @param OrderInterface $order
     * @return void
     */
    protected function setBackorderNotificationToExtensionAttributes(OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        }

        $backorderNotificationSent = $order->getData('backorder_notification_sent') ?: '0';
        $extensionAttributes->setBackorderNotificationSent($backorderNotificationSent);
        
        $order->setExtensionAttributes($extensionAttributes);
    }
}

