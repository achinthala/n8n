<?php

namespace WeltPixel\GA4\Plugin\ServerSide;

class SelectItemGtm
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
        )
    {
        $this->ga4Helper = $ga4Helper;
    }

    /**
     * @param \WeltPixel\GoogleTagManager\Helper\Data $subject
     * @param $result
     * @return bool
     */
    public function afterIsProductClickTrackingEnabled(
        \WeltPixel\GoogleTagManager\Helper\Data $subject,
        $result)
    {
        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_SELECT_ITEM)) {
            return $result;
        }

        return true;
    }


}
