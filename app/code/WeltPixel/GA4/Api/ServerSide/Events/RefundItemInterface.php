<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface RefundItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return RefundItemInterface
     */
    function setParams($options);
}
