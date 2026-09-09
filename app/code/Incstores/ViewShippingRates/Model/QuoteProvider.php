<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Amasty\RequestQuote\Api\QuoteRepositoryInterface as AmastyQuoteRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Incstores\ViewShippingRates\Model\CustomerAddressProvider;

class QuoteProvider
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var AmastyQuoteRepositoryInterface|null
     */
    private $amastyQuoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerAddressProvider
     */
    private $customerAddressProvider;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CollectionFactory $quoteCollectionFactory
     * @param AmastyQuoteRepositoryInterface|null $amastyQuoteRepository
     * @param LoggerInterface $logger
     * @param CustomerAddressProvider $customerAddressProvider
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CollectionFactory $quoteCollectionFactory,
        AmastyQuoteRepositoryInterface $amastyQuoteRepository = null,
        LoggerInterface $logger = null,
        CustomerAddressProvider $customerAddressProvider = null
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->amastyQuoteRepository = $amastyQuoteRepository;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->customerAddressProvider = $customerAddressProvider ?: ObjectManager::getInstance()->get(CustomerAddressProvider::class);
    }

    /**
     * Get quote by quote number/ID
     *
     * @param string $quoteNumber
     * @return array|null
     */
    public function getQuoteByNumber(string $quoteNumber): ?array
    {
        try {
            // First try to get as Amasty quote if repository is available
            if ($this->amastyQuoteRepository && is_numeric($quoteNumber)) {
                $quoteId = (int) $quoteNumber;
                
                // Check if this is an Amasty quote
                if ($this->amastyQuoteRepository->isAmastyQuote($quoteId)) {
                    return $this->getAmastyQuoteData($quoteId);
                }
            }
            
            // Fallback to standard Magento quote handling
            return $this->getStandardQuoteData($quoteNumber);

        } catch (NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get Amasty quote data
     *
     * @param int $quoteId
     * @return array|null
     */
    private function getAmastyQuoteData(int $quoteId): ?array
    {
        try {
            $this->logger->info('QuoteProvider - Getting Amasty quote', ['quote_id' => $quoteId]);
            
            $quote = $this->amastyQuoteRepository->get($quoteId);
            
            if (!$quote->getId()) {
                $this->logger->info('QuoteProvider - Quote not found', ['quote_id' => $quoteId]);
                return null;
            }

            $this->logger->info('QuoteProvider - Amasty quote found', [
                'quote_id' => $quote->getId(),
                'customer_email' => $quote->getCustomerEmail(),
                'customer_firstname' => $quote->getCustomerFirstname(),
                'customer_lastname' => $quote->getCustomerLastname(),
                'status' => $quote->getStatus(),
                'created_at' => $quote->getCreatedAt()
            ]);

            // Get shipping address
            $shippingAddress = $quote->getShippingAddress();
            
            $this->logger->info('QuoteProvider - Shipping address data', [
                'address_id' => $shippingAddress ? $shippingAddress->getId() : null,
                'street' => $shippingAddress ? $shippingAddress->getStreetLine(1) : null,
                'city' => $shippingAddress ? $shippingAddress->getCity() : null,
                'region' => $shippingAddress ? $shippingAddress->getRegion() : null,
                'postcode' => $shippingAddress ? $shippingAddress->getPostcode() : null,
                'country_id' => $shippingAddress ? $shippingAddress->getCountryId() : null,
                'has_shipping_address' => $shippingAddress !== null
            ]);
            
            // Get line items
            $lineItems = [];
            $allItems = $quote->getAllVisibleItems();
            
            $this->logger->info('QuoteProvider - Processing line items', [
                'total_items' => count($allItems)
            ]);
            
            foreach ($allItems as $item) {
                $weight = $item->getWeight();
                if ($weight === null || $weight === '') {
                    $weight = 1; // Default weight
                }
                
                // Get product name - for configurable products, use parent name if child name is generic
                $productName = $item->getName();
                
                // For configurable products, try to get a more descriptive name
                if ($item->getProductType() === 'simple' && $item->getParentItem()) {
                    $parentName = $item->getParentItem()->getName();
                    // If the simple product name is just the SKU or very generic, use parent + options
                    if ($productName === $item->getSku() || strlen($productName) < 10) {
                        $productName = $parentName;
                        // Add selected options if available
                        $options = $item->getProductOptions();
                        if (isset($options['attributes_info']) && is_array($options['attributes_info'])) {
                            $optionStrings = [];
                            foreach ($options['attributes_info'] as $option) {
                                if (isset($option['label']) && isset($option['value'])) {
                                    $optionStrings[] = $option['label'] . ': ' . $option['value'];
                                }
                            }
                            if (!empty($optionStrings)) {
                                $productName .= ' (' . implode(', ', $optionStrings) . ')';
                            }
                        }
                    }
                }
                
                $lineItem = [
                    'id' => (string) $item->getId(),
                    'sku' => $item->getSku(),
                    'name' => $productName,
                    'quantity' => (int) $item->getQty(),
                    'unitWeight' => (int) ceil((float) $weight)
                ];
                
                $lineItems[] = $lineItem;
                
                $this->logger->info('QuoteProvider - Line item processed', $lineItem);
            }

            $result = [
                'quoteId' => $quote->getId(),
                'reservedOrderId' => $quote->getReservedOrderId(),
                'customerEmail' => $quote->getCustomerEmail() ?: $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname(),
                'shippingAddress' => [
                    'street' => $shippingAddress ? $shippingAddress->getStreetLine(1) : '',
                    'city' => $shippingAddress ? $shippingAddress->getCity() : '',
                    'state' => $shippingAddress ? $shippingAddress->getRegion() : '',
                    'zipCode' => $shippingAddress ? $shippingAddress->getPostcode() : '',
                    'country' => $shippingAddress ? $shippingAddress->getCountryId() : 'US'
                ],
                'lineItems' => $lineItems,
                'itemCount' => count($lineItems),
                'createdAt' => $quote->getCreatedAt(),
                'isAmastyQuote' => true,
                'status' => $quote->getStatus(),
                'hasShippingAddress' => $shippingAddress && $shippingAddress->getPostcode()
            ];

            // If quote doesn't have a shipping address, get customer addresses
            if (!$result['hasShippingAddress'] && $result['customerEmail']) {
                $this->logger->info('QuoteProvider - Quote has no shipping address, fetching customer addresses');
                
                $customerAddressData = $this->customerAddressProvider->getCustomerAddressesByEmail($result['customerEmail']);
                
                if (!empty($customerAddressData)) {
                    $result['customerAddresses'] = $customerAddressData;
                    $this->logger->info('QuoteProvider - Customer addresses added to quote data', [
                        'address_count' => count($customerAddressData['addresses'] ?? [])
                    ]);
                } else {
                    $this->logger->info('QuoteProvider - No customer addresses found');
                    $result['customerAddresses'] = [];
                }
            }
            
            $this->logger->info('QuoteProvider - Final Amasty quote data', $result);
            
            return $result;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get standard Magento quote data
     *
     * @param string $quoteNumber
     * @return array|null
     */
    private function getStandardQuoteData(string $quoteNumber): ?array
    {
        try {
            // Try to get quote by ID first
            if (is_numeric($quoteNumber)) {
                $quote = $this->quoteRepository->get((int) $quoteNumber);
            } else {
                // Search by reserved order ID or other identifier
                $collection = $this->quoteCollectionFactory->create();
                $collection->addFieldToFilter('reserved_order_id', $quoteNumber);
                $quote = $collection->getFirstItem();
                
                if (!$quote->getId()) {
                    return null;
                }
            }

            if (!$quote->getId()) {
                return null;
            }

            // Get shipping address
            $shippingAddress = $quote->getShippingAddress();
            
            // Get line items
            $lineItems = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                $weight = $item->getWeight();
                if ($weight === null || $weight === '') {
                    $weight = 1; // Default weight
                }
                
                // Get product name - for configurable products, use parent name if child name is generic
                $productName = $item->getName();
                
                // For configurable products, try to get a more descriptive name
                if ($item->getProductType() === 'simple' && $item->getParentItem()) {
                    $parentName = $item->getParentItem()->getName();
                    // If the simple product name is just the SKU or very generic, use parent + options
                    if ($productName === $item->getSku() || strlen($productName) < 10) {
                        $productName = $parentName;
                        // Add selected options if available
                        $options = $item->getProductOptions();
                        if (isset($options['attributes_info']) && is_array($options['attributes_info'])) {
                            $optionStrings = [];
                            foreach ($options['attributes_info'] as $option) {
                                if (isset($option['label']) && isset($option['value'])) {
                                    $optionStrings[] = $option['label'] . ': ' . $option['value'];
                                }
                            }
                            if (!empty($optionStrings)) {
                                $productName .= ' (' . implode(', ', $optionStrings) . ')';
                            }
                        }
                    }
                }
                
                $lineItems[] = [
                    'id' => (string) $item->getId(),
                    'sku' => $item->getSku(),
                    'name' => $productName,
                    'quantity' => (int) $item->getQty(),
                    'unitWeight' => (int) ceil((float) $weight)
                ];
            }

            return [
                'quoteId' => $quote->getId(),
                'reservedOrderId' => $quote->getReservedOrderId(),
                'customerEmail' => $quote->getCustomerEmail(),
                'shippingAddress' => [
                    'street' => $shippingAddress->getStreetLine(1),
                    'city' => $shippingAddress->getCity(),
                    'state' => $shippingAddress->getRegion(),
                    'zipCode' => $shippingAddress->getPostcode(),
                    'country' => $shippingAddress->getCountryId()
                ],
                'lineItems' => $lineItems,
                'itemCount' => count($lineItems),
                'createdAt' => $quote->getCreatedAt(),
                'isAmastyQuote' => false
            ];

        } catch (NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Search quotes by customer email or reserved order ID
     *
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function searchQuotes(string $searchTerm, int $limit = 10): array
    {
        $collection = $this->quoteCollectionFactory->create();
        
        if (!empty($searchTerm)) {
            $collection->addFieldToFilter(
                [
                    ['field' => 'customer_email', 'condition' => ['like' => '%' . $searchTerm . '%']],
                    ['field' => 'reserved_order_id', 'condition' => ['like' => '%' . $searchTerm . '%']]
                ]
            );
        }
        
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize($limit);
        
        $quotes = [];
        foreach ($collection as $quote) {
            $itemCount = $quote->getItemsCount();
            $customerInfo = $quote->getCustomerEmail() ?: 'Guest';
            
            $quotes[] = [
                'value' => $quote->getId(),
                'label' => sprintf(
                    'Quote #%s - %s (%d items)',
                    $quote->getReservedOrderId() ?: $quote->getId(),
                    $customerInfo,
                    $itemCount
                ),
                'quoteId' => $quote->getId(),
                'reservedOrderId' => $quote->getReservedOrderId(),
                'customerEmail' => $quote->getCustomerEmail(),
                'itemCount' => $itemCount
            ];
        }
        
        return $quotes;
    }
}