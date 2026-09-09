<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\SelectItemItemInterface;

class SelectItemItem implements SelectItemItemInterface
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
     * @return SelectItemItemInterface
     */
    public function setParams($options)
    {
        $this->options = $options;
        return $this;
    }


}
