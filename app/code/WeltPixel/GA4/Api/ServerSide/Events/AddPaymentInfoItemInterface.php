<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddPaymentInfoItemInterface
{
    /**
     * @return array
     */
    function getParams();

    /**
     * @param array $options
     * @return AddPaymentInfoItemInterface
     */
    function setParams($options);
}
