<?php
namespace WeltPixel\GA4\Block\MetaPixel;

/**
 * Class \WeltPixel\GA4\Block\MetaPixel\Common
 */
class Common extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \WeltPixel\GA4\Helper\MetaPixelTracking
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \WeltPixel\GA4\Helper\MetaPixelTracking $helper,
        array $data = []
    )
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isMetaPixelTrackingEnabled()
    {
        return $this->helper->isMetaPixelTrackingEnabled();
    }

    /**
     * @return string
     */
    public function getMetaPixelTrackingCode()
    {
        return $this->helper->getMetaPixelCodeSnippet();
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldMetaPixelEventBeTracked($eventName)
    {
        return $this->helper->shouldMetaPixelEventBeTracked($eventName);
    }

    /**
     * @param $array
     * @return string
     */
    public function arrayToCommaSeparatedString($array)
    {
        return implode(',', array_map(function ($i) {
            return '"' . $i . '"';
        }, $array));
    }
}
