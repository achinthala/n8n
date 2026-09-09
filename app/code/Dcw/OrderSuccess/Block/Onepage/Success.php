<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\OrderSuccess\Block\Onepage;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;

/**
 * One page checkout success page
 *
 * @api
 * @since 100.0.2
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{


	/**
     *Get all the information of last order placed
     *
     * @return object
     */
    public function getAdditionalInfoHtml()
    {
        return $this->_layout->renderElement('order.success.additional.info');
    }

    public function getOrderDetail() {
        return $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * Return Opptions Configurable Product
     *
     * @param object $item
     * @return array
     */
    public function getItemOptions($item)
    {
        $result = [];
        $option = $item->getProductOptions();
        if ($option) {
            if (isset($option['options'])) {
                    $result = array_merge($result, $option['options']);
            }
            if (isset($option['additional_options'])) {
                    unset($option['additional_options']['configurable_product_url']);
                    $result = array_merge($result, $option['additional_options']);
            }
            if (isset($option['attributes_info'])) {
                    $result = array_merge($result, $option['attributes_info']);
            }
        }
        return $result;
    }
    /**
     * Format Price
     *
     * @param float $value
     * @return float
     */
    public function formatPrice($value)
    {
       //  $priceHelper = $this->pricingHelper->create();
       //  return $priceHelper->currency($value, true, false);
	   return '$'.$value;
    }
}
