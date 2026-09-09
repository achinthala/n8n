<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface SearchBuilderInterface
{
    /**
     * @param $searchTerm
     * @return null|SearchInterface
     */
    function getSearchEvent($searchTerm);
}
