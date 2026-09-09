<?php
namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class MultiShippingSuccessPageViewObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        $purchaseData = [];

        foreach ($orderIds as $orderId) {
            $orderBlock = $this->ga4Helper->createGA4Block('Order', 'order.phtml');

            if ($orderBlock) {
                $order = $this->orderRepository->get($orderId);
                $orderBlock->setOrder($order);
                $orderBlock->toHtml();
            }

            if ($this->ga4Helper->isCustomDimensionPageTypeEnabled()) {
                $pageType = \WeltPixel\GA4\Model\Api\Remarketing::ECOMM_PAGETYPE_PURCHASE;
                $this->ga4Helper->setStorageData('pageType', $pageType);
            }

            $serverSideOrderTracking = $this->ga4Helper->createGA4Block('Track\\Order', 'serverside/checkout/success.phtml');
            if ($serverSideOrderTracking) {
                $order = $this->orderRepository->get($orderId);
                $serverSideOrderTracking->setOrder($order);
                $serverSideOrderTracking->toHtml();
            }

            $purchaseData[] = $this->ga4Helper->getStorageData();
            $this->ga4Helper->unsetStorageData();
        }

        foreach ($purchaseData as $purchase) {
            $this->ga4Helper->setAdditionalDataLayerData($purchase);
        }

        return $this;
    }
}
