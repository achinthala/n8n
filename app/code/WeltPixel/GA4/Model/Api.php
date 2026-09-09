<?php

namespace WeltPixel\GA4\Model;

/**
 * Class \WeltPixel\GA4\Model\Api
 */
class Api extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Item types
     */
    const TYPE_VARIABLE_DATALAYER = 'v';
    const TYPE_VARIABLE_CONSTANT = 'c';
    const TYPE_VARIABLE_AWEC = 'awec';
    const TYPE_TRIGGER_CUSTOM_EVENT = 'customEvent';
    const TYPE_TRIGGER_PAGEVIEW = 'pageview';
    const TYPE_TAG_GAAWC = 'gaawc';
    const TYPE_TAG_GAAWE = 'gaawe';
    const TYPE_TAG_AWCT = 'awct';
    const TYPE_TAG_SP = 'sp';

    /**
     * Variable names
     */
    const VARIABLE_MEASUREMENT_ID = 'WP - MEASUREMENT ID';
    const VARIABLE_CUSTOMER_ID = 'WP - GA4 - user_id';
    const VARIABLE_CUSTOMER_GROUP = 'WP - GA4 - customerGroup';
    const VARIABLE_PAGE_TYPE = 'WP - GA4 - Page Type';
    const VARIABLE_ECOMMERCE_ITEMS = 'WP - GA4 - ecommerce.items';
    const VARIABLE_ECOMMERCE_ITEM_LIST_ID = 'WP - GA4 - ecommerce.item_list_id';
    const VARIABLE_ECOMMERCE_ITEM_LIST_NAME = 'WP - GA4 - ecommerce.item_list_name';
    const VARIABLE_TRANSACTION_ID = 'WP - GA4 - transaction_id';
    const VARIABLE_COUPON = 'WP - GA4 - coupon';
    const VARIABLE_TAX = 'WP - GA4 - tax';
    const VARIABLE_SHIPPING = 'WP - GA4 - shipping';
    const VARIABLE_CURRENCY = 'WP - GA4 - currency';
    const VARIABLE_ORDER_VALUE = 'WP - GA4 - Order Value';
    const VARIABLE_CUSTOMER_TOTAL_ORDER_COUNT = 'WP - GA4 - Customer - total_order_count';
    const VARIABLE_CUSTOMER_TOTAL_LIFETIME_VALUE = 'WP - GA4 - Customer - total_lifetime_value';
    const VARIABLE_PURCHASE_VALUE = 'WP - GA4 - Purchase Value';
    const VARIABLE_PAYMENT_TYPE = 'WP - GA4 - Payment Type';
    const VARIABLE_SHIPPING_TIER = 'WP - GA4 - Shipping Tier';
    const VARIABLE_SEARCH_TERM = 'WP - GA4 - Search Term';
    const VARIABLE_LOGIN = 'WP - GA4 - Login';
    const VARIABLE_SIGNUP = 'WP - GA4 - Signup';
    const VARIABLE_PROMOTION_CREATIVE_NAME = 'WP - GA4 - Promotion Creative Name';
    const VARIABLE_PROMOTION_CREATIVE_SLOT = 'WP - GA4 - Promotion Creative Slot';
    const VARIABLE_PROMOTION_ID = 'WP - GA4 - Promotion Id';
    const VARIABLE_PROMOTION_NAME = 'WP - GA4 - Promotion Promotion Name';

    /**
     * Trigger names
     */
    const TRIGGER_SELECT_ITEM = 'WP - GA4 - select_item';
    const TRIGGER_GTM_DOM = 'WP - GA4 - gtm.dom';
    const TRIGGER_ADD_TO_CART = 'WP - GA4 - add_to_cart';
    const TRIGGER_REMOVE_FROM_CART = 'WP - GA4 - remove_from_cart';
    const TRIGGER_VIEW_CART = 'WP - GA4 - view_cart';
    const TRIGGER_VIEW_ITEM = 'WP - GA4 - view_item';
    const TRIGGER_VIEW_ITEM_LIST = 'WP - GA4 - view_item_list';
    const TRIGGER_SELECT_PROMOTION = 'WP - GA4 - select_promotion';
    const TRIGGER_VIEW_PROMOTION = 'WP - GA4 - view_promotion';
    const TRIGGER_BEGIN_CHECKOUT = 'WP - GA4 - begin_checkout';
    const TRIGGER_PURCHASE = 'WP - GA4 - purchase';
    const TRIGGER_ADD_SHIPPING_INFO = 'WP - GA4 - add_shipping_info';
    const TRIGGER_ADD_PAYMENT_INFO = 'WP - GA4 - add_payment_info';
    const TRIGGER_ADD_TO_WISHLIST = 'WP - GA4 - add_to_wishlist';
    const TRIGGER_SEARCH = 'WP - GA4 - search';
    const TRIGGER_LOGIN = 'WP - GA4 - login';
    const TRIGGER_SIGNUP = 'WP - GA4 - sign_up';

    const TRIGGER_ALL_PAGES_ID = '2147479553';

    /**
     * Tag names
     */
    const TAG_MEASUREMENT_ID = 'WP - GA4';
    const TAG_ITEM_LIST_VIEWS_IMPRESSIONS = 'WP - GA4 - item list views/impressions';
    const TAG_PRODUCT_ITEM_LIST_CLICKS = 'WP - GA4 - product/item list clicks';
    const TAG_ITEM_ADD_TO_CART = 'WP - GA4 - add to cart';
    const TAG_ITEM_REMOVE_FROM_CART = 'WP - GA4 - remove from cart';
    const TAG_VIEW_CART = 'WP - GA4 - view cart';
    const TAG_ITEM_VIEWS_IMPRESSIONS = 'WP - GA4 - item views/impressions';
    const TAG_VIEW_PROMOTION = 'WP - GA4 - View Promotion';
    const TAG_CLICK_PROMOTION = 'WP - GA4 - Click Promotion';
    const TAG_BEGIN_CHECKOUT = 'WP - GA4 - Begin Checkout';
    const TAG_PURCHASE = 'WP - GA4 - Purchase';
    const TAG_ADD_SHIPPING_INFO = 'WP - GA4 - Add Shipping Info';
    const TAG_ADD_PAYMENT_INFO = 'WP - GA4 - Add Payment Info';
    const TAG_ADD_TO_WISHLIST = 'WP - GA4 - Add To Wishlist';
    const TAG_SEARCH = 'WP - GA4 - Search';
    const TAG_LOGIN = 'WP - GA4 - Login';
    const TAG_SIGNUP = 'WP - GA4 - Signup';

    /**
     * Return list of variables for api creation
     * @param $measurementId
     * @return array
     */
    private function _getVariables($measurementId)
    {
        $variables = [
            self::VARIABLE_MEASUREMENT_ID => [
                'name' => self::VARIABLE_MEASUREMENT_ID,
                'type' => self::TYPE_VARIABLE_CONSTANT,
                'parameter' => [
                    [
                        'type' => 'template',
                        'key' => 'value',
                        'value' => $measurementId
                    ]
                ]
            ],
            self::VARIABLE_PAGE_TYPE => [
                'name' => self::VARIABLE_PAGE_TYPE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'pageType'
                    ]
                ]
            ],
            self::VARIABLE_ECOMMERCE_ITEMS => [
                'name' => self::VARIABLE_ECOMMERCE_ITEMS,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.items'
                    ]
                ]
            ],
            self::VARIABLE_ECOMMERCE_ITEM_LIST_ID => [
                'name' => self::VARIABLE_ECOMMERCE_ITEM_LIST_ID,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.item_list_id'
                    ]
                ]
            ],
            self::VARIABLE_ECOMMERCE_ITEM_LIST_NAME => [
                'name' => self::VARIABLE_ECOMMERCE_ITEM_LIST_NAME,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.item_list_name'
                    ]
                ]
            ],
            self::VARIABLE_CUSTOMER_ID => [
                'name' => self::VARIABLE_CUSTOMER_ID,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'user_id'
                    ]
                ]
            ],
            self::VARIABLE_CUSTOMER_GROUP => [
                'name' => self::VARIABLE_CUSTOMER_GROUP,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'customerGroup'
                    ]
                ]
            ],
            self::VARIABLE_TRANSACTION_ID => [
                'name' => self::VARIABLE_TRANSACTION_ID,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.transaction_id'
                    ]
                ]
            ],
            self::VARIABLE_COUPON => [
                'name' => self::VARIABLE_COUPON,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.coupon'
                    ]
                ]
            ],
            self::VARIABLE_TAX => [
                'name' => self::VARIABLE_TAX,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.tax'
                    ]
                ]
            ],
            self::VARIABLE_SHIPPING => [
                'name' => self::VARIABLE_SHIPPING,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.shipping'
                    ]
                ]
            ],
            self::VARIABLE_CURRENCY => [
                'name' => self::VARIABLE_CURRENCY,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.currency'
                    ]
                ]
            ],
            self::VARIABLE_ORDER_VALUE => [
                'name' => self::VARIABLE_ORDER_VALUE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'value'
                    ]
                ]
            ],
            self::VARIABLE_CUSTOMER_TOTAL_ORDER_COUNT => [
                'name' => self::VARIABLE_CUSTOMER_TOTAL_ORDER_COUNT,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.total_order_count'
                    ]
                ]
            ],
            self::VARIABLE_CUSTOMER_TOTAL_LIFETIME_VALUE => [
                'name' => self::VARIABLE_CUSTOMER_TOTAL_LIFETIME_VALUE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.total_lifetime_value'
                    ]
                ]
            ],
            self::VARIABLE_PURCHASE_VALUE => [
                'name' => self::VARIABLE_PURCHASE_VALUE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.value'
                    ]
                ]
            ],
            self::VARIABLE_PAYMENT_TYPE => [
                'name' => self::VARIABLE_PAYMENT_TYPE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.payment_type'
                    ]
                ]
            ],
            self::VARIABLE_SHIPPING_TIER => [
                'name' => self::VARIABLE_SHIPPING_TIER,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.shipping_tier'
                    ]
                ]
            ],
            self::VARIABLE_SEARCH_TERM => [
                'name' => self::VARIABLE_SEARCH_TERM,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.search_term'
                    ]
                ]
            ],
            self::VARIABLE_LOGIN => [
                'name' => self::VARIABLE_LOGIN,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.method'
                    ]
                ]
            ],
            self::VARIABLE_SIGNUP => [
                'name' => self::VARIABLE_SIGNUP,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.method'
                    ]
                ]
            ],
            self::VARIABLE_PROMOTION_CREATIVE_NAME => [
                'name' => self::VARIABLE_PROMOTION_CREATIVE_NAME,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.creative_name'
                    ]
                ]
            ],
            self::VARIABLE_PROMOTION_CREATIVE_SLOT => [
                'name' => self::VARIABLE_PROMOTION_CREATIVE_SLOT,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.creative_slot'
                    ]
                ]
            ],
            self::VARIABLE_PROMOTION_ID => [
                'name' => self::VARIABLE_PROMOTION_ID,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.promotion_id'
                    ]
                ]
            ],
            self::VARIABLE_PROMOTION_NAME => [
                'name' => self::VARIABLE_PROMOTION_NAME,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => 'ecommerce.promotion_name'
                    ]
                ]
            ]
        ];
        return $variables;
    }

    /**
     * Return list of triggers for api creation
     * @return array
     */
    private function _getTriggers()
    {
        $triggers = [
            self::TRIGGER_GTM_DOM => [
                'name' => self::TRIGGER_GTM_DOM,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'gtm.dom'
                            ]
                        ]
                    ]
                ],
                'filter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{Event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'gtm.dom'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_SELECT_ITEM => [
                'name' => self::TRIGGER_SELECT_ITEM,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'select_item'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_ADD_TO_CART => [
                'name' => self::TRIGGER_ADD_TO_CART,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'add_to_cart'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_REMOVE_FROM_CART => [
                'name' => self::TRIGGER_REMOVE_FROM_CART,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'remove_from_cart'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_VIEW_CART => [
                'name' => self::TRIGGER_VIEW_CART,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'view_cart'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_SELECT_PROMOTION => [
                'name' => self::TRIGGER_SELECT_PROMOTION,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'select_promotion'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_BEGIN_CHECKOUT => [
                'name' => self::TRIGGER_BEGIN_CHECKOUT,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'begin_checkout'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_VIEW_ITEM_LIST => [
                'name' => self::TRIGGER_VIEW_ITEM_LIST,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'view_item_list'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_VIEW_ITEM => [
                'name' => self::TRIGGER_VIEW_ITEM,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'view_item'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_VIEW_PROMOTION => [
                'name' => self::TRIGGER_VIEW_PROMOTION,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'view_promotion'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_PURCHASE => [
                'name' => self::TRIGGER_PURCHASE,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'purchase'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_ADD_SHIPPING_INFO => [
                'name' => self::TRIGGER_ADD_SHIPPING_INFO,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'add_shipping_info'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_ADD_PAYMENT_INFO => [
                'name' => self::TRIGGER_ADD_PAYMENT_INFO,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'add_payment_info'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_ADD_TO_WISHLIST => [
                'name' => self::TRIGGER_ADD_TO_WISHLIST ,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'add_to_wishlist'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_SEARCH => [
                'name' => self::TRIGGER_SEARCH ,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'search'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_LOGIN => [
                'name' => self::TRIGGER_LOGIN ,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'login'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_SIGNUP => [
                'name' => self::TRIGGER_SIGNUP ,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'sign_up'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $triggers;
    }

    /**
     * Return list of tags for api creation
     * @param array $triggers
     * @param array $params
     * @return array
     */
    private function _getTags($triggers, $params)
    {
        $purchaseTagParameters = [
            [
                'type' => 'LIST',
                'key' => 'userProperties',
                'list' => [
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'customerGroup'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'user_id'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'total_order_count'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_CUSTOMER_TOTAL_ORDER_COUNT . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'total_lifetime_value'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_CUSTOMER_TOTAL_LIFETIME_VALUE . '}}'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'type' => 'TEMPLATE',
                'key' => 'eventName',
                'value' => 'purchase'
            ],
            [
                'type' => 'LIST',
                'key' => 'eventParameters',
                'list' => [
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'items'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'transaction_id'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_TRANSACTION_ID . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'tax'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_TAX . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'shipping'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_SHIPPING . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'currency'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'coupon'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_COUPON . '}}'
                            ]
                        ]
                    ],
                    [
                        'type' => 'MAP',
                        'map' => [
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'name',
                                'value' => 'value'
                            ],
                            [
                                'type' => 'TEMPLATE',
                                'key' => 'value',
                                'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'type' => 'TAG_REFERENCE',
                'key' => 'measurementId',
                'value' => self::TAG_MEASUREMENT_ID
            ],
        ];

        if ($params['send_enhanced_conversion_to_ga4']) {
            array_push($purchaseTagParameters,
                [
                    'type' => 'template',
                    'key' => 'userDataVariable',
                    'value' => '{{' . \WeltPixel\GA4\Model\Api\ConversionTracking::VARIABLE_CONVERSION_TRACKING_CUSTOMER_USER_PROVIDED_DATA . '}}'
                ],
                [
                    'type' => 'boolean',
                    'key' => 'sendEcommerceData',
                    'value' => 'false'
                ],
                [
                    'type' => 'boolean',
                    'key' => 'enhancedUserId',
                    'value' => 'true'
                ]
            );
        }

        $tags = [
            self::TAG_MEASUREMENT_ID => [
                'name' => self::TAG_MEASUREMENT_ID,
                'firingTriggerId' => [
                    self::TRIGGER_ALL_PAGES_ID
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWC,
                'parameter' => [
                    [
                        'type' => 'boolean',
                        'key' => 'sendPageView',
                        'value' => "true"
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'measurementId',
                        'value' => '{{' . self::VARIABLE_MEASUREMENT_ID . '}}'
                    ]
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_ITEM_LIST_VIEWS_IMPRESSIONS => [
                'name' => self::TAG_ITEM_LIST_VIEWS_IMPRESSIONS,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_VIEW_ITEM_LIST]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'view_item_list'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'item_list_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEM_LIST_ID . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'item_list_name'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEM_LIST_NAME . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_PRODUCT_ITEM_LIST_CLICKS => [
                'name' => self::TAG_PRODUCT_ITEM_LIST_CLICKS,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_SELECT_ITEM]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'select_item'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'MAP',
                            'map' => [
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'name',
                                    'value' => 'item_list_id'
                                ],
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'value',
                                    'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEM_LIST_ID . '}}'
                                ]
                            ]
                        ],
                        [
                            'type' => 'MAP',
                            'map' => [
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'name',
                                    'value' => 'item_list_name'
                                ],
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'value',
                                    'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEM_LIST_NAME . '}}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_ITEM_ADD_TO_CART => [
                'name' => self::TAG_ITEM_ADD_TO_CART,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_ADD_TO_CART]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'add_to_cart'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'value'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'currency'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_ITEM_REMOVE_FROM_CART => [
                'name' => self::TAG_ITEM_REMOVE_FROM_CART,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_REMOVE_FROM_CART]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'remove_from_cart'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'value'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'currency'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_VIEW_CART => [
                'name' => self::TAG_VIEW_CART,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_VIEW_CART]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'view_cart'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'value'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'currency'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_ITEM_VIEWS_IMPRESSIONS => [
                'name' => self::TAG_ITEM_VIEWS_IMPRESSIONS,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_VIEW_ITEM]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'view_item'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'value'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'currency'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_VIEW_PROMOTION => [
                'name' => self::TAG_VIEW_PROMOTION,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_VIEW_PROMOTION]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'view_promotion'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'creative_name'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_CREATIVE_NAME . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'creative_slot'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_CREATIVE_SLOT . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'promotion_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_ID . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'promotion_name'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_NAME . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_CLICK_PROMOTION => [
                'name' => self::TAG_CLICK_PROMOTION,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_SELECT_PROMOTION]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'select_promotion'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'creative_name'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_CREATIVE_NAME . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'creative_slot'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_CREATIVE_SLOT . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'promotion_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_ID . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'promotion_name'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PROMOTION_NAME . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_BEGIN_CHECKOUT => [
                'name' => self::TAG_BEGIN_CHECKOUT,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_BEGIN_CHECKOUT]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'begin_checkout'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'value'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'currency'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'coupon'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_COUPON . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_PURCHASE => [
                'name' => self::TAG_PURCHASE,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_PURCHASE]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => $purchaseTagParameters,
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_ADD_SHIPPING_INFO => [
                'name' => self::TAG_ADD_SHIPPING_INFO,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_ADD_SHIPPING_INFO]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'BOOLEAN',
                        'key' => 'sendEcommerceData',
                        'value' => 'false'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'add_shipping_info'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'value'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'currency'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'coupon'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_COUPON . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'shipping_tier'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_SHIPPING_TIER . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_ADD_PAYMENT_INFO => [
                'name' => self::TAG_ADD_PAYMENT_INFO,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_ADD_PAYMENT_INFO]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'BOOLEAN',
                        'key' => 'sendEcommerceData',
                        'value' => 'false'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'add_payment_info'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'value'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'currency'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'coupon'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_COUPON . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'payment_type'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_PAYMENT_TYPE . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_ADD_TO_WISHLIST => [
                'name' => self::TAG_ADD_TO_WISHLIST,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_ADD_TO_WISHLIST]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'BOOLEAN',
                        'key' => 'sendEcommerceData',
                        'value' => 'false'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'add_to_wishlist'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'items'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_ECOMMERCE_ITEMS . '}}'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'MAP',
                            'map' => [
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'name',
                                    'value' => 'value'
                                ],
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'value',
                                    'value' => '{{' . self::VARIABLE_PURCHASE_VALUE . '}}'
                                ]
                            ]
                        ],
                        [
                            'type' => 'MAP',
                            'map' => [
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'name',
                                    'value' => 'currency'
                                ],
                                [
                                    'type' => 'TEMPLATE',
                                    'key' => 'value',
                                    'value' => '{{' . self::VARIABLE_CURRENCY . '}}'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_SEARCH => [
                'name' => self::TAG_SEARCH,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_SEARCH]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'BOOLEAN',
                        'key' => 'sendEcommerceData',
                        'value' => 'false'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'search'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'search_term'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_SEARCH_TERM . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_LOGIN => [
                'name' => self::TAG_LOGIN,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_LOGIN]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'BOOLEAN',
                        'key' => 'sendEcommerceData',
                        'value' => 'false'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'login'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'method'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_LOGIN . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ],
            self::TAG_SIGNUP => [
                'name' => self::TAG_SIGNUP,
                'firingTriggerId' => [
                    $triggers[self::TRIGGER_SIGNUP]
                ],
                'tagFiringOption' => 'oncePerEvent',
                'type' => self::TYPE_TAG_GAAWE,
                'parameter' => [
                    [
                        'type' => 'BOOLEAN',
                        'key' => 'sendEcommerceData',
                        'value' => 'false'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'userProperties',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'customerGroup'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_GROUP . '}}'
                                    ]
                                ]
                            ],
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'user_id'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_CUSTOMER_ID . '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TEMPLATE',
                        'key' => 'eventName',
                        'value' => 'sign_up'
                    ],
                    [
                        'type' => 'LIST',
                        'key' => 'eventParameters',
                        'list' => [
                            [
                                'type' => 'MAP',
                                'map' => [
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'name',
                                        'value' => 'method'
                                    ],
                                    [
                                        'type' => 'TEMPLATE',
                                        'key' => 'value',
                                        'value' => '{{' . self::VARIABLE_SIGNUP. '}}'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'TAG_REFERENCE',
                        'key' => 'measurementId',
                        'value' => self::TAG_MEASUREMENT_ID
                    ],
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ]
        ];

        return $tags;
    }

    /**
     * @param string $measurementId
     * @return array
     */
    public function getVariablesList($measurementId)
    {
        return $this->_getVariables($measurementId);
    }

    /**
     * @return array
     */
    public function getTriggersList()
    {
        return $this->_getTriggers();
    }

    /**
     * @param array $triggersMapping
     * @param array $params
     * @return array
     */
    public function getTagsList($triggersMapping, $params)
    {
        return $this->_getTags($triggersMapping, $params);
    }
}
