<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface SignupBuilderInterface
{
    /**
     * @param $customerId
     * @return null|SignupInterface
     */
    function getSignupEvent($customerId);
}
