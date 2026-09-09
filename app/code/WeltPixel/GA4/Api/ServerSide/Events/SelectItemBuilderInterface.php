<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface SelectItemBuilderInterface
{
    /**
     * @param $productId
     * @param $listId
     * @param $listName
     * @param $index
     * @return null|SelectItemInterface
     */
    function getSelectItemEvent($productId, $listId, $listName, $index);
}
