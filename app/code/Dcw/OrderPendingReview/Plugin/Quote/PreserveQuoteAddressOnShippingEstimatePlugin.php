<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Quote;

use Dcw\OrderPendingReview\Model\Quote\CartEstimateAddressPreserver;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Magento estimate-shipping-methods addData() can blank name/street/phone from a ZIP-only payload.
 */
class PreserveQuoteAddressOnShippingEstimatePlugin
{
    public function __construct(
        private readonly CartEstimateAddressPreserver $preserver
    ) {
    }

    /**
     * @param mixed $subject
     * @param mixed $cartId
     * @return array{0: mixed, 1: AddressInterface}
     */
    public function beforeEstimateByExtendedAddress(
        $subject,
        $cartId,
        AddressInterface $address
    ): array {
        $this->preserver->fillBlankCustomerFieldsFromQuote($cartId, $address);

        return [$cartId, $address];
    }
}
