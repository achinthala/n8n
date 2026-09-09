<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Incstores\ViewShippingRates\Model\CustomerAddressProvider;

class OrderProvider
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerAddressProvider
     */
    private $customerAddressProvider;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param LoggerInterface $logger
     * @param CustomerAddressProvider $customerAddressProvider
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CollectionFactory $orderCollectionFactory,
        LoggerInterface $logger = null,
        CustomerAddressProvider $customerAddressProvider = null
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->customerAddressProvider = $customerAddressProvider ?: ObjectManager::getInstance()->get(CustomerAddressProvider::class);
    }

    /**
     * Get order by order number/ID
     *
     * @param string $orderNumber
     * @return array|null
     */
    public function getOrderByNumber(string $orderNumber): ?array
    {
        try {
            $this->logger->info('OrderProvider - Getting order', ['order_number' => $orderNumber]);
            
            $order = null;
            
            // First try to get by entity ID if numeric
            if (is_numeric($orderNumber)) {
                try {
                    $order = $this->orderRepository->get((int) $orderNumber);
                } catch (NoSuchEntityException $e) {
                    // Fall through to increment ID search
                }
            }
            
            // If not found by ID or not numeric, search by increment ID
            if (!$order || !$order->getId()) {
                $collection = $this->orderCollectionFactory->create();
                $collection->addFieldToFilter('increment_id', $orderNumber);
                $order = $collection->getFirstItem();
                
                if (!$order->getId()) {
                    $this->logger->info('OrderProvider - Order not found', ['order_number' => $orderNumber]);
                    return null;
                }
            }

            $this->logger->info('OrderProvider - Order found', [
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_firstname' => $order->getCustomerFirstname(),
                'customer_lastname' => $order->getCustomerLastname(),
                'status' => $order->getStatus(),
                'state' => $order->getState(),
                'created_at' => $order->getCreatedAt()
            ]);

            // Get shipping address
            $shippingAddress = $order->getShippingAddress();
            
            $this->logger->info('OrderProvider - Shipping address data', [
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
            $allItems = $order->getAllVisibleItems();
            
            $this->logger->info('OrderProvider - Processing line items', [
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
                    'quantity' => (int) $item->getQtyOrdered(),
                    'unitWeight' => (int) ceil((float) $weight)
                ];
                
                $lineItems[] = $lineItem;
                
                $this->logger->info('OrderProvider - Line item processed', $lineItem);
            }

            $result = [
                'orderId' => $order->getId(),
                'incrementId' => $order->getIncrementId(),
                'customerEmail' => $order->getCustomerEmail() ?: $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                'shippingAddress' => [
                    'street' => $shippingAddress ? $shippingAddress->getStreetLine(1) : '',
                    'city' => $shippingAddress ? $shippingAddress->getCity() : '',
                    'state' => $shippingAddress ? $shippingAddress->getRegion() : '',
                    'zipCode' => $shippingAddress ? $shippingAddress->getPostcode() : '',
                    'country' => $shippingAddress ? $shippingAddress->getCountryId() : 'US'
                ],
                'lineItems' => $lineItems,
                'itemCount' => count($lineItems),
                'createdAt' => $order->getCreatedAt(),
                'isOrder' => true,
                'status' => $order->getStatus(),
                'state' => $order->getState(),
                'hasShippingAddress' => $shippingAddress && $shippingAddress->getPostcode()
            ];

            // If order doesn't have a shipping address (virtual order), get customer addresses
            if (!$result['hasShippingAddress'] && $result['customerEmail']) {
                $this->logger->info('OrderProvider - Order has no shipping address, fetching customer addresses');
                
                $customerAddressData = $this->customerAddressProvider->getCustomerAddressesByEmail($result['customerEmail']);
                
                if (!empty($customerAddressData)) {
                    $result['customerAddresses'] = $customerAddressData;
                    $this->logger->info('OrderProvider - Customer addresses added to order data', [
                        'address_count' => count($customerAddressData['addresses'] ?? [])
                    ]);
                } else {
                    $this->logger->info('OrderProvider - No customer addresses found');
                    $result['customerAddresses'] = [];
                }
            }
            
            $this->logger->info('OrderProvider - Final order data', $result);
            
            return $result;

        } catch (NoSuchEntityException $e) {
            $this->logger->info('OrderProvider - Order not found (NoSuchEntityException)', ['order_number' => $orderNumber]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('OrderProvider - Exception getting order', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Search orders by customer email or increment ID
     *
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function searchOrders(string $searchTerm, int $limit = 10): array
    {
        $collection = $this->orderCollectionFactory->create();
        
        if (!empty($searchTerm)) {
            $collection->addFieldToFilter(
                [
                    ['field' => 'customer_email', 'condition' => ['like' => '%' . $searchTerm . '%']],
                    ['field' => 'increment_id', 'condition' => ['like' => '%' . $searchTerm . '%']]
                ]
            );
        }
        
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize($limit);
        
        $orders = [];
        foreach ($collection as $order) {
            $itemCount = $order->getTotalItemCount();
            $customerInfo = $order->getCustomerEmail() ?: 'Guest';
            
            $orders[] = [
                'value' => $order->getId(),
                'label' => sprintf(
                    'Order #%s - %s (%d items) - %s',
                    $order->getIncrementId(),
                    $customerInfo,
                    $itemCount,
                    $order->getStatus()
                ),
                'orderId' => $order->getId(),
                'incrementId' => $order->getIncrementId(),
                'customerEmail' => $order->getCustomerEmail(),
                'itemCount' => $itemCount,
                'status' => $order->getStatus(),
                'state' => $order->getState()
            ];
        }
        
        return $orders;
    }
}