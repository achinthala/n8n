<?php
namespace Dotcomweavers\OrderRestrictions\Api;

/**
 * Interface ShippingAddressManagementInterface
 * @api
 * @package Dotcomweavers\OrderRestrictions\Api
 */
interface ShippingAddressManagementInterface
{
    /**
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return int Customer ID
     */
    public function saveAddress(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    );

    /**
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return int Customer ID
     */
    public function deleteAddress(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    );
}
