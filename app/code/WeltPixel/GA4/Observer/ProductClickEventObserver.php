<?php
namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductClickEventObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4ServerSideHelper;

    /**
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper
     */
    public function __construct(\WeltPixel\GA4\Helper\Data $helper,
                                \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper)
    {
        $this->helper = $helper;
        $this->ga4ServerSideHelper = $ga4ServerSideHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $serverSideTracking = $this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_SELECT_ITEM);
        if (!$serverSideTracking && (!$this->helper->isEnabled() || !$this->helper->isProductClickTrackingEnabled())) {
            return $this;
        }

        $productClickHtmlObject = $observer->getData('html');
        $product = $observer->getData('product');
        $productIndex = $observer->getData('index');
        $productListValue = $observer->getData('list');
        $productListId = $observer->getData('listId');
        $html = $productClickHtmlObject->getHtml();
        $html .= $this->helper->getProductClickHtml($product, $productIndex, $productListValue, $productListId);
        $productClickHtmlObject->setHtml($html);

        return $this;
    }
}
