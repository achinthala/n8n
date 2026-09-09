<?php
namespace WeltPixel\GA4\Observer\MetaPixel;

use Magento\Framework\Event\ObserverInterface;

class CartUpdateItemOptionsAfter implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\MetaPixelTracking
     */
    protected $metaPixelTrackingHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;


    /**
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     */
    public function __construct(
        \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper,
        \Magento\Checkout\Model\Session $_checkoutSession
    )
    {
        $this->metaPixelTrackingHelper = $metaPixelTrackingHelper;
        $this->_checkoutSession = $_checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->metaPixelTrackingHelper->isMetaPixelTrackingEnabled() || !$this->metaPixelTrackingHelper->shouldMetaPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\MetaPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            return $this;
        }

        $item = $observer->getData('item');

        if ($item->getQtyBeforeChange() != $item->getQty()) {
            $qtyChange =  $item->getQty() - $item->getQtyBeforeChange();
            if ($qtyChange != 0) {
                if ($qtyChange > 0) {
                    $addToCartPushData = $this->metaPixelTrackingHelper->metaPixelAddToCartPushData($item->getProduct(), $qtyChange);
                    $initialAddTocartPushData =  $this->_checkoutSession->getMetaPixelAddToCartData() ?? [];
                    $initialAddTocartPushData[] = $addToCartPushData;
                    $this->_checkoutSession->setMetaPixelAddToCartData($initialAddTocartPushData);
                }
            }
        }

        return $this;
    }
}
