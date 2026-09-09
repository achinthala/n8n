<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface BeginCheckoutItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return BeginCheckoutItemInterface
     */
    function setParams($options);
}
