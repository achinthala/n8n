<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface PurchaseBuilderInterface
{
    /**
     * @param $order
     * @return null|PurchaseInterface
     */
    function getPurchaseEvent($order);
}
