<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddToWishlistBuilderInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $buyRequest
     * @param \Magento\Wishlist\Model\Item $wishlistItem
     * @return null|AddToWishlistInterface
     */
    function getAddToWishlistEvent($product, $buyRequest, $wishlistItem);
}
