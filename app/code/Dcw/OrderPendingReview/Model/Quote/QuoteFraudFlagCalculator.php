<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\Quote;

use Dcw\OrderPendingReview\Model\FraudFlag;
use Magento\Directory\Model\RegionFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

/**
 * Sets quote.dcw_fraud_flag: 2 if failed attempts ≥ threshold, else 1 if $1K+ and regions differ, else 0.
 */
class QuoteFraudFlagCalculator
{
    public function __construct(
        private readonly RegionFactory $regionFactory
    ) {
    }
    public function apply(Quote $quote): void
    {
        if (!$quote->getId()) {
            return;
        }

        $attempts = (int) $quote->getData('dcw_failed_payment_attempts');
        if ($attempts >= FraudFlag::minFailedAttempts()) {
            $quote->setData('dcw_fraud_flag', FraudFlag::FAILED_PAYMENTS);

            return;
        }

        if (
            (float) $quote->getBaseGrandTotal() >= FraudFlag::minBaseGrandTotal()
            && $this->billingShippingRegionsDiffer($quote)
        ) {
            $quote->setData('dcw_fraud_flag', FraudFlag::ADDRESS_MISMATCH_1K);

            return;
        }

        $quote->setData('dcw_fraud_flag', FraudFlag::NONE);
    }

    private function billingShippingRegionsDiffer(Quote $quote): bool
    {
        $billing = $quote->getBillingAddress();
        $shipping = $quote->getShippingAddress();
        if (!$billing || !$shipping) {
            return false;
        }
        $billRegion = $this->normalizeAddressRegion($billing);
        $shipRegion = $this->normalizeAddressRegion($shipping);

        return $billRegion !== '' && $shipRegion !== '' && strcasecmp($billRegion, $shipRegion) !== 0;
    }

    /**
     * Resolves region from code, region_id (directory), or name — quote addresses often omit region_code.
     */
    private function normalizeAddressRegion(Address $address): string
    {
        $code = trim((string) $address->getRegionCode());
        if ($code !== '') {
            return $code;
        }

        $regionId = (int) $address->getRegionId();
        if ($regionId > 0) {
            $region = $this->regionFactory->create()->load($regionId);
            if ($region->getId()) {
                $loadedCode = trim((string) $region->getCode());
                if ($loadedCode !== '') {
                    return $loadedCode;
                }
                $name = trim((string) $region->getDefaultName());
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return trim((string) $address->getRegion());
    }
}
