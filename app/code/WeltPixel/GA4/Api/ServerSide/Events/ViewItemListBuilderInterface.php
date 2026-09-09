<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewItemListBuilderInterface
{
    /**
     * @param $paramsOptions
     * @return null|ViewItemListInterface
     */
    function getViewItemListEvent($paramsOptions);
}
