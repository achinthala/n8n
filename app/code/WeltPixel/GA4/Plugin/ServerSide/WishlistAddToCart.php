<?php

namespace WeltPixel\GA4\Plugin\ServerSide;

class WishlistAddToCart
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface */
    protected $addToCartBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface $addToCartBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface $addToCartBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
        )
    {
        $this->ga4Helper = $ga4Helper;
        $this->_checkoutSession = $checkoutSession;
        $this->addToCartBuilder = $addToCartBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
    }

    /**
     * @param \Magento\Wishlist\Model\Item $subject
     * @param $result
     * @return bool
     * @throws \Magento\Catalog\Model\Product\Exception
     */
    public function afterAddToCart(
        \Magento\Wishlist\Model\Item $subject,
        $result)
    {

        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_CART)) {
            return $result;
        }

        if ($result) {
            $buyRequest = $subject->getBuyRequest();
            $qty = $buyRequest->getData('qty');
            $product = $subject->getProduct();

            $addToCartEvent = $this->addToCartBuilder->getAddToCartEvent($product, $qty, $buyRequest, true);
            $this->ga4ServerSideApi->pushAddToCartEvent($addToCartEvent);
        }

        return $result;
    }


}
