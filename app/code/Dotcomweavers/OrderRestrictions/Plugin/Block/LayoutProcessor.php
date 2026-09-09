<?php declare(strict_types=1);

namespace Dotcomweavers\OrderRestrictions\Plugin\Block;

class LayoutProcessor
{
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['customer_address_id'] = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/hidden',
                'id' => 'customer_address_id'
            ],
            'dataScope' => 'shippingAddress.customer_address_id',
            'label' => 'Customer Address Id',
            'provider' => 'checkoutProvider',
            'visible' => false,
            'validation' => [],
            'sortOrder' => 250,
            'id' => 'customer_address_id'
        ];
        return $jsLayout;
    }
}
