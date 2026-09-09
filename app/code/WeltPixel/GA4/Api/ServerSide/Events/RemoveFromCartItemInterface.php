<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface RemoveFromCartItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return RemoveFromCartItemInterface
     */
    function setParams($options);
}
