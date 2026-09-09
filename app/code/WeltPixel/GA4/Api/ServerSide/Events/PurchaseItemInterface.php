<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface PurchaseItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return PurchaseItemInterface
     */
    function setParams($options);
}
