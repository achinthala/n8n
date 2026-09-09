<?php
namespace Dcw\CheckoutExtraField\Plugin\Checkout;

class LayoutProcessorPlugin
{
public function afterProcess(
\Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
array $jsLayout
) {
// Add phone_ext field to shipping address form
$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
['shippingAddress']['children']['shipping-address-fieldset']['children']['phone_ext'] = [
'component' => 'Magento_Ui/js/form/element/abstract',
'config' => [
'customScope' => 'shippingAddress',
'template' => 'ui/form/field',
'elementTmpl' => 'ui/form/element/input'
],
'dataScope' => 'shippingAddress.phone_ext',
'label' => __('Phone No Ext.'),
'provider' => 'checkoutProvider',
'sortOrder' => 115,
'validation' => [
'required-entry' => false
],
'options' => [],
'filterBy' => null,
'customEntry' => null,
'visible' => true,
];
return $jsLayout;
}
}