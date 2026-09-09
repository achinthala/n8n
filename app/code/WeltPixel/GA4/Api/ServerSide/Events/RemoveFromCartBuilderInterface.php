<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface RemoveFromCartBuilderInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param double $quantity
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return null|RemoveFromCartInterface
     */
    function getRemoveFromCartEvent($product, $quantity, $quoteItem);
}
