<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewCartItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return ViewCartItemInterface
     */
    function setParams($options);
}
