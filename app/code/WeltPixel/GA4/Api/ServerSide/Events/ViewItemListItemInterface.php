<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewItemListItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return ViewItemListItemInterface
     */
    function setParams($options);
}
