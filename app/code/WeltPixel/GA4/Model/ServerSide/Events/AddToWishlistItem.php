<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Wishlist\Block\AddToWishlist;
use WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistItemInterface;

class AddToWishlistItem implements AddToWishlistItemInterface
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
     * @return AddToWishlistItemInterface
     */
    public function setParams($options)
    {
        $this->options = $options;
        return $this;
    }


}
