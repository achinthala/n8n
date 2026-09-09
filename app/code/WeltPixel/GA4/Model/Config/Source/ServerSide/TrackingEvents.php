<?php

namespace WeltPixel\GA4\Model\Config\Source\ServerSide;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TrackingEvents
 *
 * @package WeltPixel\GA4\Model\Config\Source\ServerSide
 */
class TrackingEvents implements ArrayInterface
{

    const EVENT_PURCHASE = 'purchase';
    const EVENT_REFUND = 'refund';
    const EVENT_SIGNUP = 'sign_up';
    const EVENT_LOGIN = 'login';
    const EVENT_VIEW_ITEM = 'view_item';
    const EVENT_VIEW_ITEM_LIST = 'view_item_list';
    const EVENT_SELECT_ITEM = 'select_item';
    const EVENT_SEARCH = 'search';
    const EVENT_ADD_TO_CART = 'add_to_cart';
    const EVENT_REMOVE_FROM_CART = 'remove_from_cart';
    const EVENT_VIEW_CART = 'view_cart';
    const EVENT_BEGIN_CHECKOUT = 'begin_checkout';
    const EVENT_ADD_PAYMENT_INFO = 'add_payment_info';
    const EVENT_ADD_SHIPPING_INFO = 'add_shipping_info';
    const EVENT_ADD_TO_WISHLIST = 'add_to_wishlist';

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
                'value' => self::EVENT_REFUND,
                'label' => __('Refund')
            ),
            array(
                'value' => self::EVENT_SIGNUP,
                'label' => __('Sign Up')
            ),
            array(
                'value' => self::EVENT_LOGIN,
                'label' => __('Login')
            ),
            array(
                'value' => self::EVENT_VIEW_ITEM,
                'label' => __('View Item')
            ),
            array(
                'value' => self::EVENT_VIEW_ITEM_LIST,
                'label' => __('View Item List')
            ),
            array(
                'value' => self::EVENT_SELECT_ITEM,
                'label' => __('Select Item')
            ),
            array(
                'value' => self::EVENT_SEARCH,
                'label' => __('Search')
            ),
            array(
                'value' => self::EVENT_ADD_TO_WISHLIST,
                'label' => __('Add To Wishlist')
            ),
            array(
                'value' => self::EVENT_ADD_TO_CART,
                'label' => __('Add To Cart')
            ),
            array(
                'value' => self::EVENT_REMOVE_FROM_CART,
                'label' => __('Remove From Cart')
            ),
            array(
                'value' => self::EVENT_VIEW_CART,
                'label' => __('View Cart')
            ),
            array(
                'value' => self::EVENT_BEGIN_CHECKOUT,
                'label' => __('Begin Checkout')
            ),
            array(
                'value' => self::EVENT_ADD_PAYMENT_INFO,
                'label' => __('Add Payment Info')
            ),
            array(
                'value' => self::EVENT_ADD_SHIPPING_INFO,
                'label' => __('Add Shipping Info')
            )
        );
    }
}
