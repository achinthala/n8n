<?php

namespace WeltPixel\GA4\Observer\MetaPixel;

use Magento\Framework\Event\ObserverInterface;

class WishListAddProductObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\MetaPixelTracking
     */
    protected $metaPixelTrackingHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;


    /**
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper
     * @param \Magento\Customer\Model\Sessiong $customerSession
     */
    public function __construct(\WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper,
                                \Magento\Customer\Model\Session $customerSession)
    {
        $this->metaPixelTrackingHelper = $metaPixelTrackingHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->metaPixelTrackingHelper->isMetaPixelTrackingEnabled() || !$this->metaPixelTrackingHelper->shouldMetaPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\MetaPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            return $this;
        }

        $product = $observer->getData('product');

        $addToWishlistPushData = $this->metaPixelTrackingHelper->metaPixelAddToWishlistPushData($product);
        $initialAddToWishlistPushData =  $this->customerSession->getMetaPixelAddToWishlistData() ?? [];
        $initialAddToWishlistPushData[] = $addToWishlistPushData;
        $this->customerSession->setMetaPixelAddToWishlistData($initialAddToWishlistPushData);

        return $this;
    }
}
