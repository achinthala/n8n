<?php

namespace WeltPixel\GA4\Plugin;

use Magento\Checkout\Model\Cart as CustomerCart;

class CartUpdateItemOptions
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

    /**
     * @var \WeltPixel\GA4\Helper\MetaPixelTracking
     */
    protected $metaPixelTrackingHelper;

    /**
     * @var CustomerCart
     */
    protected $cart;

    /**
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper
     * @param CustomerCart $cart
     */
    public function __construct(
        \WeltPixel\GA4\Helper\Data $helper,
        \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper,
        CustomerCart $cart
    ) {
        $this->helper = $helper;
        $this->metaPixelTrackingHelper = $metaPixelTrackingHelper;
        $this->cart = $cart;
    }

    /**
     * @param \Magento\Checkout\Controller\Cart\UpdateItemOptions $subject
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(\Magento\Checkout\Controller\Cart\UpdateItemOptions $subject)
    {
        if (!$this->helper->isEnabled() && !($this->metaPixelTrackingHelper->isEnabled() && $this->metaPixelTrackingHelper->shouldMetaPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\MetaPixel\TrackingEvents::EVENT_ADD_TO_CART))) {
            return $this;
        }

        $id = (int)$subject->getRequest()->getParam('id');
        $quoteItem = $this->cart->getQuote()->getItemById($id);

        if ($quoteItem) {
            $quoteItem->setQtyBeforeChange($quoteItem->getQty());
        }
    }
}
