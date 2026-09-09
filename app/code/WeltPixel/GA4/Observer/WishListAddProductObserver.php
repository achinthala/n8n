<?php

namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class WishListAddProductObserver implements ObserverInterface
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
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;


    /**
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper
     * @param \Magento\Customer\Model\Sessiong $customerSession
     */
    public function __construct(\WeltPixel\GA4\Helper\Data $helper,
                                \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper,
                                \Magento\Customer\Model\Session $customerSession)
    {
        $this->helper = $helper;
        $this->ga4ServerSideHelper = $ga4ServerSideHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        if (($this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_WISHLIST)
            && $this->ga4ServerSideHelper->isDataLayerEventDisabled())) {
            return $this;
        }

        $product = $observer->getData('product');
        $wishlistItem = $observer->getData('item');
        $buyRequest = $wishlistItem->getBuyRequest()->getData();

        $this->customerSession->setGA4AddToWishListData($this->helper->addToWishListPushData($product, $buyRequest, $wishlistItem));

        return $this;
    }
}
