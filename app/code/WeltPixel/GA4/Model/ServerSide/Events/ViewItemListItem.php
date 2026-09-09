<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\ViewItemListItemInterface;

class ViewItemListItem implements ViewItemListItemInterface
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
     * @return ViewItemListItemInterface
     */
    public function setParams($options)
    {
        $this->options = $options;
        return $this;
    }


}
