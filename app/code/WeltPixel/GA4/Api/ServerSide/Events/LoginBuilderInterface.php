<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface LoginBuilderInterface
{
    /**
     * @param $customerId
     * @return null|LoginInterface
     */
    function getLoginEvent($customerId);
}
