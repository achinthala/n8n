<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CustomerAddressProvider
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Get customer addresses by customer email
     *
     * @param string $customerEmail
     * @return array
     */
    public function getCustomerAddressesByEmail(string $customerEmail): array
    {
        try {
            $this->logger->info('CustomerAddressProvider - Getting addresses for customer', [
                'email' => $customerEmail
            ]);

            // Get customer by email
            $customer = $this->customerRepository->get($customerEmail);
            
            if (!$customer->getId()) {
                $this->logger->info('CustomerAddressProvider - Customer not found', [
                    'email' => $customerEmail
                ]);
                return [];
            }

            $this->logger->info('CustomerAddressProvider - Customer found', [
                'customer_id' => $customer->getId(),
                'email' => $customerEmail
            ]);

            // Get customer addresses
            $addresses = $customer->getAddresses();
            
            if (empty($addresses)) {
                $this->logger->info('CustomerAddressProvider - No addresses found for customer', [
                    'customer_id' => $customer->getId()
                ]);
                return [];
            }

            $addressData = [];
            foreach ($addresses as $address) {
                $street = $address->getStreet();
                $streetLine1 = is_array($street) ? (isset($street[0]) ? $street[0] : '') : (string) $street;
                
                $addressItem = [
                    'id' => $address->getId(),
                    'firstname' => $address->getFirstname(),
                    'lastname' => $address->getLastname(),
                    'company' => $address->getCompany(),
                    'street' => $streetLine1,
                    'city' => $address->getCity(),
                    'region' => $address->getRegion() ? $address->getRegion()->getRegion() : '',
                    'postcode' => $address->getPostcode(),
                    'country_id' => $address->getCountryId(),
                    'telephone' => $address->getTelephone(),
                    'is_default_billing' => $address->isDefaultBilling(),
                    'is_default_shipping' => $address->isDefaultShipping(),
                    'display_name' => $this->formatAddressForDisplay($address)
                ];

                $addressData[] = $addressItem;
                
                $this->logger->info('CustomerAddressProvider - Address processed', [
                    'address_id' => $address->getId(),
                    'postcode' => $address->getPostcode(),
                    'is_default_shipping' => $address->isDefaultShipping()
                ]);
            }

            $this->logger->info('CustomerAddressProvider - Addresses retrieved successfully', [
                'customer_id' => $customer->getId(),
                'address_count' => count($addressData)
            ]);

            return [
                'customer_id' => $customer->getId(),
                'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'addresses' => $addressData
            ];

        } catch (NoSuchEntityException $e) {
            $this->logger->info('CustomerAddressProvider - Customer not found', [
                'email' => $customerEmail,
                'error' => $e->getMessage()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->error('CustomerAddressProvider - Error getting customer addresses', [
                'email' => $customerEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get specific address by ID for a customer
     *
     * @param int $addressId
     * @param string $customerEmail
     * @return array|null
     */
    public function getAddressById(int $addressId, string $customerEmail): ?array
    {
        try {
            $customerAddresses = $this->getCustomerAddressesByEmail($customerEmail);
            
            if (empty($customerAddresses['addresses'])) {
                return null;
            }

            foreach ($customerAddresses['addresses'] as $address) {
                if ($address['id'] == $addressId) {
                    return $address;
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->error('CustomerAddressProvider - Error getting address by ID', [
                'address_id' => $addressId,
                'email' => $customerEmail,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Format address for display in dropdown
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    private function formatAddressForDisplay(\Magento\Customer\Api\Data\AddressInterface $address): string
    {
        $parts = [];
        
        $street = $address->getStreet();
        $streetLine1 = is_array($street) ? (isset($street[0]) ? $street[0] : '') : (string) $street;
        
        if ($streetLine1) {
            $parts[] = $streetLine1;
        }
        
        if ($address->getCity()) {
            $parts[] = $address->getCity();
        }
        
        if ($address->getRegion() && $address->getRegion()->getRegion()) {
            $parts[] = $address->getRegion()->getRegion();
        }
        
        if ($address->getPostcode()) {
            $parts[] = $address->getPostcode();
        }

        $displayName = implode(', ', $parts);
        
        // Add default shipping indicator
        if ($address->isDefaultShipping()) {
            $displayName .= ' (Default Shipping)';
        } elseif ($address->isDefaultBilling()) {
            $displayName .= ' (Default Billing)';
        }

        return $displayName;
    }
}