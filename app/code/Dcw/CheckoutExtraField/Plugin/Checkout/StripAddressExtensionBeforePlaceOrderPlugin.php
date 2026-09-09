<?php
declare(strict_types=1);

namespace Dcw\CheckoutExtraField\Plugin\Checkout;

use Dcw\CheckoutExtraField\Model\Quote\AddressExtensionObjectStripper;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Must run before Magento_CheckoutStaging (sortOrder 0), which array_diff()s address getData().
 */
class StripAddressExtensionBeforePlaceOrderPlugin
{
    public function __construct(
        private readonly AddressExtensionObjectStripper $stripper
    ) {
    }

    /**
     * @param mixed $cartId
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        $this->stripper->stripFromCartAndBilling($cartId, $billingAddress);
    }
}
