<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class RefundObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\RefundBuilderInterface */
    protected $refundBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\RefundBuilderInterface $refundBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\RefundBuilderInterface $refundBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->refundBuilder = $refundBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_REFUND)) {
            return $this;
        }

        $creditMemo = $observer->getData('creditmemo') ?? false;

        if ($creditMemo) {
            $refundEvent = $this->refundBuilder->getRefundEvent($creditMemo);
            $this->ga4ServerSideApi->pushRefundEvent($refundEvent);
        }

        return $this;
    }
}
