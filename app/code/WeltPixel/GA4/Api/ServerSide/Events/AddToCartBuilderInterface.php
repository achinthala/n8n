<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddToCartBuilderInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param double $quantity
     * @param array $buyRequest
     * @param boolean $checkForCustomOptions
     * @return null|AddToCartInterface
     */
    function getAddToCartEvent($product, $quantity, $buyRequest = [], $checkForCustomOptions = false);
}
