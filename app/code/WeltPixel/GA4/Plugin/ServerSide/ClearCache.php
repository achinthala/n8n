<?php

namespace WeltPixel\GA4\Plugin\ServerSide;

class ClearCache
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
     * @param \WeltPixel\GA4\Helper\Data $subject
     * @param $result
     * @return bool
     */
    public function afterCleanType(
        \Magento\Framework\App\Cache\TypeListInterface $subject,
        $result, $typeCode)
    {
        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_VIEW_ITEM_LIST)) {
            return $result;
        }

        if ($typeCode == \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER) {
            $this->ga4ServerSideJsonBuilder->clearSavedHashes();
        }

        return $result;
    }


}
