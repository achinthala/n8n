<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddShippingInfoItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return AddShippingInfoItemInterface
     */
    function setParams($options);
}
