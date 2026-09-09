<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class AddToWishlistObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistBuilderInterface */
    protected $addToWishlistBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistBuilderInterface $addToWishlistBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistBuilderInterface $addToWishlistBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->addToWishlistBuilder = $addToWishlistBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            return $this;
        }

        $product = $observer->getData('product');
        $wishlistItem = $observer->getData('item');
        $buyRequest = $wishlistItem->getBuyRequest()->getData();

        $addToWishlistEvent = $this->addToWishlistBuilder->getAddToWishlistEvent($product, $buyRequest, $wishlistItem);
        $this->ga4ServerSideApi->pushAddToWishlistEvent($addToWishlistEvent);

        return $this;
    }
}
