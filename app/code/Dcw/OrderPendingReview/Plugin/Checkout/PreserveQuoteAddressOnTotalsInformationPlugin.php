<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Checkout;

use Dcw\OrderPendingReview\Model\Quote\CartEstimateAddressPreserver;
use Magento\Checkout\Api\Data\TotalsInformationInterface;

/**
 * Magento totals-information replaces quote shipping with the cart estimate payload (ZIP/country).
 */
class PreserveQuoteAddressOnTotalsInformationPlugin
{
    public function __construct(
        private readonly CartEstimateAddressPreserver $preserver
    ) {
    }

    /**
     * @param mixed $subject
     * @param mixed $cartId
     * @return array{0: mixed, 1: TotalsInformationInterface}
     */
    public function beforeCalculate(
        $subject,
        $cartId,
        TotalsInformationInterface $addressInformation
    ): array {
        $address = $addressInformation->getAddress();
        if ($address) {
            $this->preserver->fillBlankCustomerFieldsFromQuote($cartId, $address, true);
        }

        return [$cartId, $addressInformation];
    }
}
