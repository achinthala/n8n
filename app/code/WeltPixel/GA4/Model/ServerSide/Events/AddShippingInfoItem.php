<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoItemInterface;

class AddShippingInfoItem implements AddShippingInfoItemInterface
{
    /**
     * @var array
     */
    protected $options;


    public function __construct()
    {
        $this->options = [];
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return AddShippingInfoItemInterface
     */
    public function setParams($options)
    {
        $this->options = $options;
        return $this;
    }


}
