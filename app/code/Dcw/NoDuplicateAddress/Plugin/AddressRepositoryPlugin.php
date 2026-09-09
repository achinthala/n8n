<?php

declare(strict_types=1);

namespace Dcw\NoDuplicateAddress\Plugin;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

class AddressRepositoryPlugin
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * Constructor
     *
     * @param SearchCriteriaBuilderFactory $criteriaFactory
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        SearchCriteriaBuilderFactory $criteriaFactory,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->criteriaFactory = $criteriaFactory;
        $this->addressRepository = $addressRepository;
    }

    /**
     * aroundSave() → if duplicate found, return existing address
     */
    public function aroundSave(
        AddressRepositoryInterface $subject,
        callable $proceed,
        AddressInterface $address
    ): AddressInterface {

        // Only dedupe **customer** addresses (skip admin/global saves)
        if (!$address->getCustomerId()) {
            return $proceed($address);
        }

        // 1. Look for an identical address owned by this customer
        $existing = $this->findExistingAddress($address);

        if ($existing) {
            // ---- DUPLICATE FOUND ----
            // Copy its ID back to the incoming object so the rest of the
            // checkout flow uses that ID (prevents "Address ID is null")
            $address->setId($existing->getId());

            // Set default billing and shipping based on incoming address

            if ($address->isDefaultBilling()) {
                $existing->setIsDefaultBilling(true);
            }
            return $proceed($existing);
            // Return existing address without saving to avoid duplicate
        }

        // 2. No match → let Magento save it normally
        return $proceed($address);
    }
    /* ------------------------------------------------------------------ */
    /*  Helpers                                                           */
    /* ------------------------------------------------------------------ */

    private function findExistingAddress(AddressInterface $addr): ?AddressInterface
    {
        $criteria = $this->criteriaFactory->create()
            ->addFilter('parent_id', $addr->getCustomerId())        // same customer
            ->create();

        foreach ($this->addressRepository->getList($criteria)->getItems() as $item) {
            if ($this->areEqual($addr, $item)) {
                return $item;
            }
        }
        return null;
    }

    private function areEqual(AddressInterface $a, AddressInterface $b): bool
    {
        return $this->norm($a->getFirstname())   === $this->norm($b->getFirstname())
            && $this->norm($a->getLastname())    === $this->norm($b->getLastname())
            && $this->norm($this->street($a))    === $this->norm($this->street($b))
            && $this->norm($a->getCity())        === $this->norm($b->getCity())
            && $this->norm($a->getPostcode())    === $this->norm($b->getPostcode())
            && $this->norm($a->getCountryId())   === $this->norm($b->getCountryId())
            && (string) $a->getRegionId()        === (string) $b->getRegionId()
            && $this->norm($a->getTelephone())   === $this->norm($b->getTelephone());
    }

    private function street(AddressInterface $a): string
    {
        $s = $a->getStreet();
        return is_array($s) ? implode(' ', $s) : (string) $s;
    }

    private function norm(?string $v): string
    {
        return strtolower(trim((string) $v));
    }
}
