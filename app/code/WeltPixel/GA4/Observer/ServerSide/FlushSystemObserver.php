<?php
namespace WeltPixel\GA4\Observer\ServerSide;

use Magento\Framework\Event\ObserverInterface;

class FlushSystemObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /**
     * @var \WeltPixel\GA4\Model\ServerSide\JsonBuilder
     */
    protected $ga4ServerSideJsonBuilder;


    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Model\ServerSide\JsonBuilder $ga4ServerSideJsonBuilder
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Model\ServerSide\JsonBuilder $ga4ServerSideJsonBuilder
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->ga4ServerSideJsonBuilder = $ga4ServerSideJsonBuilder;
    }

    /**
     * Add Custom layout handle
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_VIEW_ITEM_LIST)) {
            return $this;
        }

        $this->ga4ServerSideJsonBuilder->clearSavedHashes();

        return $this;
    }
}
