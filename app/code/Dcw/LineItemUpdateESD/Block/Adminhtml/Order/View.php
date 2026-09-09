<?php

namespace Dcw\LineItemUpdateESD\Block\Adminhtml\Order;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;

class View extends Template
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    public function __construct(
        Template\Context $context,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get order
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        return $this->orderRepository->get($orderId);
    }

    /**
     * Get ONLY visible items
     *
     * @return \Magento\Sales\Api\Data\OrderItemInterface[]
     */
    public function getVisibleItems()
    {
        return $this->getOrder()->getAllVisibleItems();
    }

    /**
     * Get Estimated Ship Date from pdp_line_item
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return string
     */
    public function getEstimatedShipDate($item)
    {
        $pdpLineItem = $item->getData('pdp_line_item');

        if (!$pdpLineItem) {
            return '';
        }

        $data = json_decode($pdpLineItem, true);
        return $data['shipping_estimate'] ?? '';
    }
}
