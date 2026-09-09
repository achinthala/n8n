<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddToCartItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return AddToCartItemInterface
     */
    function setParams($options);
}
