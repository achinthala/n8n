<?php

namespace WeltPixel\GA4\Model\Config\Source\MetaPixel;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TrackingEvents
 *
 * @package WeltPixel\GA4\Model\Config\Source\MetaPixel
 */
class TrackingEvents implements ArrayInterface
{
    const EVENT_PURCHASE = 'purchase';
    const EVENT_ADD_PAYMENT_INFO = 'add_payment_info';
    const EVENT_ADD_TO_CART = 'add_to_cart';
    const EVENT_ADD_TO_WISHLIST = 'add_to_wishlist';
    const EVENT_INITIATE_CHECKOUT = 'initiate_checkout';
    const EVENT_SEARCH = 'search';
    const EVENT_VIEW_CONTENT = 'view_content';
    const EVENT_VIEW_CATEGORY = 'view_category';


    /**
     * Return list of Id Options
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::EVENT_PURCHASE,
                'label' => __('Purchase')
            ),
            array(
                'value' => self::EVENT_ADD_PAYMENT_INFO,
                'label' => __('AddPaymentInfo')
            ),
            array(
                'value' => self::EVENT_ADD_TO_CART,
                'label' => __('AddToCart')
            ),
            array(
                'value' => self::EVENT_ADD_TO_WISHLIST,
                'label' => __('AddToWishlist')
            ),
            array(
                'value' => self::EVENT_INITIATE_CHECKOUT,
                'label' => __('InitiateCheckout')
            ),
            array(
                'value' => self::EVENT_SEARCH,
                'label' => __('Search')
            ),
            array(
                'value' => self::EVENT_VIEW_CONTENT,
                'label' => __('ViewContent')
            ),
            array(
                'value' => self::EVENT_VIEW_CATEGORY,
                'label' => __('ViewCategory')
            )
        );
    }
}
