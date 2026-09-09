<?php
declare(strict_types=1);

namespace Dcw\CheckoutExtraField\Plugin\Checkout;

use Dcw\CheckoutExtraField\Model\Quote\AddressExtensionObjectStripper;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Guest Magento_CheckoutStaging uses the same array_diff() on address getData().
 */
class GuestStripAddressExtensionBeforePlaceOrderPlugin
{
    public function __construct(
        private readonly AddressExtensionObjectStripper $stripper
    ) {
    }

    /**
     * @param mixed $cartId
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        $this->stripper->stripFromCartAndBilling($cartId, $billingAddress);
    }
}
