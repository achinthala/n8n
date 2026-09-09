<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartItemInterface;

class RemoveFromCartItem implements RemoveFromCartItemInterface
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
     * @return RemoveFromCartItemInterface
     */
    public function setParams($options)
    {
        $this->options = $options;
        return $this;
    }


}
