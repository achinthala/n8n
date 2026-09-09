<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface BeginCheckoutBuilderInterface
{
    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return null|BeginCheckoutInterface
     */
    function getBeginCheckoutEvent($quote);
}
