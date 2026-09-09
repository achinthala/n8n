<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\Quote;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Psr\Log\LoggerInterface;

/**
 * Place Order does not send shipping. Magento checkout sends billing (often copied from the
 * shipping form in the browser). If the quote shipping row is still the cart ZIP estimate,
 * copy missing required fields from billing before quote validation.
 */
class IncompleteShippingAddressBackfiller
{
    public const LOG_PREFIX = '[OrderPendingReview][shipping_address_backfill]';

    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function applyToCartId(int $cartId): bool
    {
        if ($cartId <= 0) {
            return false;
        }

        try {
            $quote = $this->cartRepository->getActive($cartId);
        } catch (\Throwable $e) {
            $this->logger->warning(self::LOG_PREFIX . ' could not load quote', [
                'quote_entity_id' => $cartId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            return false;
        }

        if (!$quote instanceof Quote) {
            return false;
        }

        return $this->applyAndSave($quote);
    }

    public function applyAndSave(Quote $quote): bool
    {
        if (!$this->apply($quote)) {
            return false;
        }

        try {
            $this->cartRepository->save($quote);
        } catch (\Throwable $e) {
            $this->logger->error(self::LOG_PREFIX . ' save failed', [
                'quote_entity_id' => (int) $quote->getId(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            return false;
        }

        return true;
    }

    public function apply(Quote $quote): bool
    {
        if (!$quote->getId() || $quote->isVirtual()) {
            return false;
        }

        $shipping = $quote->getShippingAddress();
        $billing = $quote->getBillingAddress();
        if (!$shipping || !$billing) {
            return false;
        }

        if (!$this->isMissingRequiredFields($shipping)) {
            return false;
        }

        $this->logger->info(self::LOG_PREFIX . ' shipping address empty, filling from billing', [
            'quote_entity_id' => (int) $quote->getId(),
            'missing_fields' => $this->missingRequiredFieldNames($shipping),
            'shipping_postcode' => (string) $shipping->getPostcode(),
            'shipping_country_id' => (string) $shipping->getCountryId(),
        ]);

        if ($this->isMissingRequiredFields($billing)) {
            $this->logger->info(self::LOG_PREFIX . ' billing also empty, cannot fill shipping', [
                'quote_entity_id' => (int) $quote->getId(),
                'missing_billing_fields' => $this->missingRequiredFieldNames($billing),
            ]);
            return false;
        }

        $copied = $this->copyCustomerAddressFields($billing, $shipping);
        if ($copied === []) {
            return false;
        }

        $shipping->setSameAsBilling(1);
        $shipping->setDataChanges(true);
        $quote->setDataChanges(true);

        $this->logger->info(self::LOG_PREFIX . ' copied billing onto incomplete shipping', [
            'quote_entity_id' => (int) $quote->getId(),
            'copied_fields' => $copied,
        ]);

        return true;
    }

    /**
     * Magento shipping-address validation that produced the fraud-flag-2 false positives:
     * firstname, lastname, street, telephone required at place order.
     */
    private function isMissingRequiredFields(Address $address): bool
    {
        return $this->missingRequiredFieldNames($address) !== [];
    }

    /**
     * @return list<string>
     */
    private function missingRequiredFieldNames(Address $address): array
    {
        $missing = [];
        if ($this->isBlank($address->getFirstname())) {
            $missing[] = 'firstname';
        }
        if ($this->isBlank($address->getLastname())) {
            $missing[] = 'lastname';
        }
        if ($this->isBlank($this->streetToString($address->getStreet()))) {
            $missing[] = 'street';
        }
        if ($this->isBlank($address->getTelephone())) {
            $missing[] = 'telephone';
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    private function copyCustomerAddressFields(Address $from, Address $to): array
    {
        $copied = [];
        foreach (['firstname', 'lastname', 'middlename', 'prefix', 'suffix', 'company', 'telephone', 'email'] as $field) {
            if (!$this->isBlank($to->getData($field)) || $this->isBlank($from->getData($field))) {
                continue;
            }
            $to->setData($field, $from->getData($field));
            $copied[] = $field;
        }

        if ($this->isBlank($this->streetToString($to->getStreet()))
            && !$this->isBlank($this->streetToString($from->getStreet()))
        ) {
            $to->setStreet($from->getStreet());
            $copied[] = 'street';
        }

        foreach (['city', 'region', 'region_id', 'region_code', 'postcode', 'country_id'] as $field) {
            if (!$this->isBlank($to->getData($field)) || $this->isBlank($from->getData($field))) {
                continue;
            }
            $to->setData($field, $from->getData($field));
            $copied[] = $field;
        }

        return $copied;
    }

    private function streetToString(mixed $street): string
    {
        if (is_array($street)) {
            return trim(implode('', $street));
        }

        return trim((string) $street);
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 0;
        }

        return trim((string) $value) === '';
    }
}
