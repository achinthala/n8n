<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddToWishlistItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return AddToWishlistItemInterface
     */
    function setParams($options);
}
