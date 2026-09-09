<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface SelectItemItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return SelectItemItemInterface
     */
    function setParams($options);
}
