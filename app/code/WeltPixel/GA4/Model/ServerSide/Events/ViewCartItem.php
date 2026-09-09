<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\ViewCartItemInterface;

class ViewCartItem implements ViewCartItemInterface
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
     * @return ViewCartItemInterface
     */
    public function setParams($options)
    {
        $this->options = $options;
        return $this;
    }


}
