<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewItemItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return ViewItemItemInterface
     */
    function setParams($options);
}
