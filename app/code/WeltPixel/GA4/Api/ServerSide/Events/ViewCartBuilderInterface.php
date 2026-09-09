<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewCartBuilderInterface
{
    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return null|ViewCartInterface
     */
    function getViewCartEvent($quote);
}
