<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class PurchaseObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\AddPaymentInfoBuilderInterface */
    protected $addPaymentInfoBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface */
    protected $purchaseBuilder;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddPaymentInfoBuilderInterface $addPaymentInfoBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder,
        \WeltPixel\GA4\Api\ServerSide\Events\AddPaymentInfoBuilderInterface $addPaymentInfoBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Framework\App\State $state
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->purchaseBuilder = $purchaseBuilder;
        $this->addPaymentInfoBuilder = $addPaymentInfoBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->state = $state;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->ga4Helper->isServerSideTrakingEnabled()) {
            return $this;
        }

        try {
            $order = $observer->getEvent()->getOrder();

            if ($this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_PAYMENT_INFO)) {
                $additionalInformation = $order->getPayment()->getAdditionalInformation();
                if ($additionalInformation && isset($additionalInformation['method_title'])) {
                    $paymentType = $additionalInformation['method_title'];
                    $addPaymentInfoEvent = $this->addPaymentInfoBuilder->getAddPaymentInfoEvent($order, $paymentType);
                    $this->ga4ServerSideApi->pushAddPaymentInfoEvent($addPaymentInfoEvent);
                }
            }


            if (($this->state->getAreaCode() == 'adminhtml') &&  $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_PURCHASE)) {
                if ($order && $this->ga4Helper->isOrderTrackingAllowedBasedOnOrderStatus($order)) {
                    $purchaseEvent = $this->purchaseBuilder->getPurchaseEvent($order);
                    $this->ga4ServerSideApi->pushPurchaseEvent($purchaseEvent);
                }
            }

        } catch (\Exception $ex) {
            return $this;
        }
        return $this;
    }
}
