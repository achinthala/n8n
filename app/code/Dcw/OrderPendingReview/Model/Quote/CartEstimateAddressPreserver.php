<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Psr\Log\LoggerInterface;

/**
 * Cart estimate APIs send only country/postcode. Magento then writes that payload onto
 * quote.shipping_address, which clears name/street/phone if they were already saved.
 */
class CartEstimateAddressPreserver
{
    public const LOG_PREFIX = '[OrderPendingReview][cart_estimate_address]';

    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly LoggerInterface $logger,
        private readonly Registry $registry
    ) {
    }

    /**
     * @param bool $logZipOnlyPersist Log why shipping becomes empty (totals-information save only)
     */
    public function fillBlankCustomerFieldsFromQuote(
        string|int $cartId,
        AddressInterface $estimateAddress,
        bool $logZipOnlyPersist = false
    ): void {
        $quoteId = $this->resolveQuoteEntityId($cartId);
        if ($quoteId <= 0) {
            return;
        }

        try {
            $quote = $this->cartRepository->getActive($quoteId);
        } catch (NoSuchEntityException) {
            return;
        } catch (\Throwable $e) {
            $this->logger->warning(self::LOG_PREFIX . ' could not load quote', [
                'cart_id' => (string) $cartId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            return;
        }

        if (!$quote instanceof Quote || $quote->isVirtual()) {
            return;
        }

        $existing = $quote->getShippingAddress();
        if (!$existing) {
            return;
        }

        if ($logZipOnlyPersist) {
            $this->logIfEstimateHasNoCustomerAddress($quoteId, $estimateAddress, $existing);
        }

        $this->copyBlankCustomerFields($existing, $estimateAddress);
    }

    /**
     * @return list<string>
     */
    private function copyBlankCustomerFields(Address $existing, AddressInterface $estimate): array
    {
        $copied = [];
        $scalarFields = [
            'firstname' => ['getFirstname', 'setFirstname'],
            'lastname' => ['getLastname', 'setLastname'],
            'middlename' => ['getMiddlename', 'setMiddlename'],
            'prefix' => ['getPrefix', 'setPrefix'],
            'suffix' => ['getSuffix', 'setSuffix'],
            'company' => ['getCompany', 'setCompany'],
            'telephone' => ['getTelephone', 'setTelephone'],
            'email' => ['getEmail', 'setEmail'],
            'city' => ['getCity', 'setCity'],
        ];

        foreach ($scalarFields as $field => [$getter, $setter]) {
            if (!$this->isBlank($estimate->{$getter}()) || $this->isBlank($existing->{$getter}())) {
                continue;
            }
            $estimate->{$setter}($existing->{$getter}());
            $copied[] = $field;
        }

        if ($this->isBlank($this->streetToString($estimate->getStreet()))
            && !$this->isBlank($this->streetToString($existing->getStreet()))
        ) {
            $estimate->setStreet($existing->getStreet());
            $copied[] = 'street';
        }

        return $copied;
    }

    /**
     * Cart getEstimateAddress() sends only country/postcode. Magento totals-information then
     * replaces quote.shipping_address with that payload — that is why name/street/phone are empty.
     */
    private function logIfEstimateHasNoCustomerAddress(
        int $quoteId,
        AddressInterface $estimate,
        Address $existing
    ): void {
        $missingOnPayload = $this->missingCustomerFieldNames($estimate);
        if ($missingOnPayload === []) {
            return;
        }

        $onceKey = 'dcw_cart_est_zip_only_' . $quoteId;
        if ($this->registry->registry($onceKey)) {
            return;
        }
        $this->registry->register($onceKey, true);

        $this->logger->info(
            self::LOG_PREFIX . ' shipping empty because cart estimate sent zip/country only',
            [
                'quote_entity_id' => $quoteId,
                'reason' => 'cart_estimate_zip_only_payload',
                'postcode' => (string) $estimate->getPostcode(),
                'country_id' => (string) $estimate->getCountryId(),
                'missing_on_estimate_payload' => $missingOnPayload,
                'already_on_quote' => $this->presentCustomerFieldNames($existing),
            ]
        );
    }

    /**
     * @return list<string>
     */
    private function missingCustomerFieldNames(AddressInterface $address): array
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
    private function presentCustomerFieldNames(Address $address): array
    {
        $present = [];
        foreach (['firstname', 'lastname', 'street', 'telephone'] as $field) {
            $value = $field === 'street'
                ? $this->streetToString($address->getStreet())
                : $address->getData($field);
            if (!$this->isBlank($value)) {
                $present[] = $field;
            }
        }

        return $present;
    }

    private function resolveQuoteEntityId(string|int $cartId): int
    {
        $id = (string) $cartId;
        if ($id !== '' && ctype_digit($id)) {
            return (int) $id;
        }
        if ($id === '') {
            return 0;
        }

        try {
            return (int) $this->maskedQuoteIdToQuoteId->execute($id);
        } catch (NoSuchEntityException) {
            return 0;
        }
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
        if (is_array($value)) {
            return $this->streetToString($value) === '';
        }

        return trim((string) $value) === '';
    }
}
