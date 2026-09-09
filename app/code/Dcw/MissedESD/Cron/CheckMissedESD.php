<?php

namespace Dcw\MissedESD\Cron;

use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as OrderItemResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Klaviyo\Reclaim\Helper\ScopeSetting as KlaviyoScopeSetting;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cron job: check order items that have not been shipped
 * and where the current date is greater than the ESD start date.
 *
 * ESD (Estimated Ship Date) is read from pdp_line_item.shipping_estimate on the order item.
 */
class CheckMissedESD
{
    /**
     * @var OrderItemCollectionFactory
     */
    private $orderItemCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var KlaviyoScopeSetting
     */
    private $klaviyoScopeSetting;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigurableType
     */
    private $configurableProductType;

    /**
     * @var OrderItemResource
     */
    private $orderItemResource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param KlaviyoScopeSetting        $klaviyoScopeSetting
     * @param ProductMetadataInterface   $productMetadata
     * @param StoreManagerInterface      $storeManager
     * @param ConfigurableType           $configurableProductType
     * @param OrderItemResource          $orderItemResource
     * @param ScopeConfigInterface       $scopeConfig
     */
    public function __construct(
        OrderItemCollectionFactory $orderItemCollectionFactory,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        KlaviyoScopeSetting $klaviyoScopeSetting,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        ConfigurableType $configurableProductType,
        OrderItemResource $orderItemResource,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->configurableProductType = $configurableProductType;
        $this->orderItemResource = $orderItemResource;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute cron: find unshipped order items past ESD start date and log them.
     *
     * @return void
     */
    public function execute()
    {
        $this->createLog('CheckMissedESD cron execution started');

        try {
            $this->createLog('Starting data collection: querying missed ESD order items');

            $items = $this->getMissedESDOrderItems();

            $this->createLog(
                'Data collection completed | missed ESD order item count: ' . count($items)
            );

            if (empty($items)) {
                $this->createLog('No missed ESD order items found; skipping notification processing');
                return;
            }

            $this->createLog('Beginning foreach loop over ' . count($items) . ' missed ESD order item(s)');

            $processedCount = 0;
            $skippedCount = 0;

            foreach ($items as $item) {
                if (!$item instanceof OrderItem) {
                    $this->createLog(
                        'Loop iteration skipped: item is not an instance of OrderItem | type: '
                        . (is_object($item) ? get_class($item) : gettype($item))
                    );
                    $skippedCount++;
                    continue;
                }

                $itemId = $item->getId();
                $orderId = $item->getOrder() ? $item->getOrder()->getIncrementId() : 'N/A';
                $sku = $item->getSku();

                $this->createLog(
                    'Loop iteration started | itemId: ' . $itemId
                    . ' | orderId: ' . $orderId
                    . ' | SKU: ' . $sku
                );

                $this->createLog('Resolving ESD start date for itemId: ' . $itemId);

                $esdStart = $this->getESDStartDate($item);

                if ($esdStart === '' || $esdStart === null) {
                    $this->createLog(
                        'Loop iteration skipped: no usable ESD start date | itemId: ' . $itemId
                        . ' | orderId: ' . $orderId
                        . ' | SKU: ' . $sku
                    );
                    $skippedCount++;
                    continue;
                }

                $this->createLog(
                    'ESD start date resolved | itemId: ' . $itemId
                    . ' | esdStart: ' . $esdStart
                );

                // Skip items that have already had the ESD delay notification sent.
                $esdDelayNotified = (int)$item->getData('esd_delay_notified');

                $this->createLog(
                    'Checking esd_delay_notified flag | itemId: ' . $itemId
                    . ' | esd_delay_notified: ' . $esdDelayNotified
                );

                if ($esdDelayNotified === 1) {
                    $this->createLog(
                        'Loop iteration skipped: ESD delay notification already sent | itemId: ' . $itemId
                        . ' | orderId: ' . $orderId
                        . ' | SKU: ' . $sku
                    );
                    $skippedCount++;
                    continue;
                }

                $this->createLog(
                    'Triggering Klaviyo ESD notification | itemId: ' . $itemId
                    . ' | orderId: ' . $orderId
                    . ' | SKU: ' . $sku
                    . ' | esdStart: ' . $esdStart
                );

                // Trigger Klaviyo notification for this order item and ESD start date.
                $this->triggerOrderItemsESDNotificationToKlaviyo($item, $esdStart);

                $this->createLog(
                    'Loop iteration completed | itemId: ' . $itemId
                    . ' | orderId: ' . $orderId
                    . ' | SKU: ' . $sku
                );

                $processedCount++;
            }

            $this->createLog(
                'Foreach loop completed | total items: ' . count($items)
                . ' | processed: ' . $processedCount
                . ' | skipped: ' . $skippedCount
            );

            $this->createLog('CheckMissedESD cron execution completed successfully');
        } catch (\Throwable $e) {
            $this->createLog(
                'CheckMissedESD cron execution failed with exception | message: ' . $e->getMessage()
                . ' | file: ' . $e->getFile()
                . ' | line: ' . $e->getLine()
            );
            // Intentionally ignored exception
            unset($e);
        } finally {
            $this->createLog('CheckMissedESD cron execution ended');
        }
    }

    /**
     * Get product categories as array of names.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $storeId
     * @return array
     */
    private function getProductCategories($product, $storeId)
    {
        $productId = $product->getId();
        $this->createLog(
            'getProductCategories started | productId: ' . $productId . ' | storeId: ' . $storeId
        );

        $categoryNames = [];

        try {
            $categoryIds = $product->getCategoryIds();
            $this->createLog(
                'Category IDs retrieved | productId: ' . $productId
                . ' | category count: ' . count($categoryIds)
            );

            if (!empty($categoryIds)) {
                foreach ($categoryIds as $categoryId) {
                    $this->createLog(
                        'Category loop iteration | productId: ' . $productId
                        . ' | categoryId: ' . $categoryId
                    );

                    try {
                        $category = $this->categoryRepository->get($categoryId, $storeId);

                        if ($category && $category->getName()) {
                            $categoryNames[] = $category->getName();
                            $this->createLog(
                                'Category loaded successfully | categoryId: ' . $categoryId
                                . ' | name: ' . $category->getName()
                            );
                        } else {
                            $this->createLog(
                                'Category loaded but name is empty | categoryId: ' . $categoryId
                            );
                        }
                    } catch (\Exception $e) {
                        $this->createLog(
                            'Failed to load category | categoryId: ' . $categoryId
                            . ' | error: ' . $e->getMessage()
                        );
                        // Continue with next category.
                        continue;
                    }
                }
            } else {
                $this->createLog('No category IDs found for productId: ' . $productId);
            }
        } catch (\Exception $e) {
            $this->createLog(
                'getProductCategories failed | productId: ' . $productId
                . ' | error: ' . $e->getMessage()
            );
            // Intentionally ignored exception
            unset($e);
        }

        $this->createLog(
            'getProductCategories completed | productId: ' . $productId
            . ' | category names count: ' . count($categoryNames)
        );

        return $categoryNames;
    }

    /**
     * Trigger OrderItemsESD notification to Klaviyo for a single order item.
     *
     * This is an inline copy of the logic from
     * Dcw\LineItemUpdateESD\Service\KlaviyoNotificationOrderItemsESD::triggerOrderItemsESDNotificationToKlaviyo()
     *
     * @param OrderItem $orderLineItem
     * @param string    $estimatedShipDate
     * @return void
     */
    public function triggerOrderItemsESDNotificationToKlaviyo(OrderItem $orderLineItem, $estimatedShipDate)
    {
        $itemId = $orderLineItem->getId();
        $order = $orderLineItem->getOrder();
        $customerEmail = $order->getCustomerEmail();
        $orderId = $order->getIncrementId();
        $lineItemName = $orderLineItem->getName();

        $this->createLog(
            'triggerOrderItemsESDNotificationToKlaviyo started | itemId: ' . $itemId
            . ' | orderId: ' . $orderId
            . ' | estimatedShipDate: ' . $estimatedShipDate
        );

        if (empty($customerEmail)) {
            $this->createLog(
                'Notification aborted: customer email is empty | itemId: ' . $itemId
                . ' | orderId: ' . $orderId
            );
            return;
        }

        $this->createLog(
            'Customer email resolved | orderId: ' . $orderId . ' | customerEmail: ' . $customerEmail
        );

        $parentItem = $orderLineItem->getParentItem();
        $parentProductId = null;
        $parentSku = null;
        $productUrl = '';
        $storeId = $order->getStoreId();
        $currentProductId = $orderLineItem->getProductId();
        $currentProductType = $orderLineItem->getProductType();

        $productForUrl = null;

        $this->createLog(
            'Resolving parent product | itemId: ' . $itemId
            . ' | currentProductId: ' . $currentProductId
            . ' | currentProductType: ' . $currentProductType
            . ' | hasParentItem: ' . ($parentItem ? 'yes' : 'no')
        );

        // Case 1: Item has a parent item (simple product with configurable parent)
        if ($parentItem) {
            $this->createLog('Parent product resolution: Case 1 - item has parent item | itemId: ' . $itemId);

            try {
                $parentProductId = $parentItem->getProductId();
                $this->createLog(
                    'Case 1 parent product ID retrieved | itemId: ' . $itemId
                    . ' | parentProductId: ' . $parentProductId
                );

                if ($parentProductId) {
                    try {
                        $this->createLog(
                            'Loading parent product by ID | parentProductId: ' . $parentProductId
                            . ' | storeId: ' . $storeId
                        );

                        $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId);
                        $parentSku = $parentProduct->getSku();
                        $productForUrl = $parentProduct;

                        $this->createLog(
                            'Case 1 parent product loaded successfully | parentProductId: ' . $parentProductId
                            . ' | parentSku: ' . $parentSku
                        );
                    } catch (\Exception $e) {
                        $this->createLog(
                            'Case 1 failed to load parent product | parentProductId: ' . $parentProductId
                            . ' | error: ' . $e->getMessage()
                        );
                        // Intentionally ignored exception
                        unset($e);
                    }
                }
            } catch (\Exception $e) {
                $this->createLog(
                    'Case 1 exception while resolving parent item | itemId: ' . $itemId
                    . ' | error: ' . $e->getMessage()
                );
                // Intentionally ignored exception
                unset($e);
            }
        } elseif ($currentProductType === 'configurable' && $currentProductId) {
            // Case 2: Item itself is a configurable product (it IS the parent)
            $this->createLog(
                'Parent product resolution: Case 2 - item is configurable | itemId: ' . $itemId
                . ' | productId: ' . $currentProductId
            );

            $parentProductId = $currentProductId;

            try {
                $this->createLog(
                    'Loading configurable product as parent | parentProductId: ' . $parentProductId
                    . ' | storeId: ' . $storeId
                );

                $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId);
                $parentSku = $parentProduct->getSku();
                $productForUrl = $parentProduct;

                $this->createLog(
                    'Case 2 configurable product loaded successfully | parentProductId: ' . $parentProductId
                    . ' | parentSku: ' . $parentSku
                );
            } catch (\Exception $e) {
                $this->createLog(
                    'Case 2 failed to load configurable product | parentProductId: ' . $parentProductId
                    . ' | error: ' . $e->getMessage()
                );
                // Intentionally ignored exception
                unset($e);
            }
        } else {
            // Case 3: Fallback - Check if this is a simple product that belongs to a configurable product
            $this->createLog(
                'Parent product resolution: Case 3 - fallback lookup via configurable child | itemId: '
                . $itemId . ' | productId: ' . $currentProductId
            );

            try {
                if ($currentProductId) {
                    $this->createLog(
                        'Querying parent IDs by child product | childProductId: ' . $currentProductId
                    );

                    $parentIds = $this->configurableProductType->getParentIdsByChild($currentProductId);

                    $this->createLog(
                        'Parent IDs query completed | childProductId: ' . $currentProductId
                        . ' | parentIds count: ' . count($parentIds)
                    );

                    if (!empty($parentIds) && isset($parentIds[0])) {
                        $parentProductId = $parentIds[0];

                        $this->createLog(
                            'Case 3 parent product ID found | childProductId: ' . $currentProductId
                            . ' | parentProductId: ' . $parentProductId
                        );

                        try {
                            $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId);
                            $parentSku = $parentProduct->getSku();
                            $productForUrl = $parentProduct;

                            $this->createLog(
                                'Case 3 parent product loaded successfully | parentProductId: '
                                . $parentProductId . ' | parentSku: ' . $parentSku
                            );
                        } catch (\Exception $e) {
                            $this->createLog(
                                'Case 3 failed to load parent product | parentProductId: ' . $parentProductId
                                . ' | error: ' . $e->getMessage()
                            );
                            // Intentionally ignored exception
                            unset($e);
                        }
                    } else {
                        $this->createLog(
                            'Case 3 no parent product IDs found | childProductId: ' . $currentProductId
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->createLog(
                    'Case 3 exception during parent lookup | itemId: ' . $itemId
                    . ' | error: ' . $e->getMessage()
                );
                // Intentionally ignored exception
                unset($e);
            }
        }

        // If no parent or product not loaded, get from current item's product
        if (!$productForUrl) {
            $this->createLog(
                'Product URL fallback: loading product from current order item | itemId: ' . $itemId
            );

            try {
                $currentProductId = $orderLineItem->getProductId();

                if ($currentProductId) {
                    $this->createLog(
                        'Loading current product for URL | productId: ' . $currentProductId
                        . ' | storeId: ' . $storeId
                    );

                    $productForUrl = $this->productRepository->getById($currentProductId, false, $storeId);

                    $this->createLog(
                        'Current product loaded for URL | productId: ' . $currentProductId
                        . ' | SKU: ' . $productForUrl->getSku()
                    );
                } else {
                    $this->createLog('Product URL fallback skipped: current product ID is empty | itemId: ' . $itemId);
                }
            } catch (\Exception $e) {
                $this->createLog(
                    'Product URL fallback failed | itemId: ' . $itemId
                    . ' | error: ' . $e->getMessage()
                );
                // Intentionally ignored exception
                unset($e);
            }
        } else {
            $this->createLog(
                'Product for URL already resolved | itemId: ' . $itemId
                . ' | productId: ' . $productForUrl->getId()
            );
        }

        if ($productForUrl && $productForUrl->getId()) {
            $productUrl = $productForUrl->getProductUrl();
            $this->createLog(
                'Product URL resolved | itemId: ' . $itemId . ' | productUrl: ' . $productUrl
            );
        } else {
            $this->createLog('Product URL could not be resolved | itemId: ' . $itemId);
        }

        $productId = $orderLineItem->getProductId();
        $sku = $orderLineItem->getSku();
        $quantity = $orderLineItem->getQtyOrdered();
        $price = $orderLineItem->getPrice();
        $total = $orderLineItem->getRowTotal();

        $this->createLog(
            'Order line item details collected | itemId: ' . $itemId
            . ' | productId: ' . $productId
            . ' | SKU: ' . $sku
            . ' | quantity: ' . $quantity
            . ' | price: ' . $price
            . ' | total: ' . $total
        );

        $product = null;
        $productCategories = [];

        try {
            if ($parentItem) {
                $this->createLog('Loading product from parent item for categories | itemId: ' . $itemId);
                $product = $parentItem->getProduct();
            } else {
                $this->createLog('Loading product from order line item for categories | itemId: ' . $itemId);
                $product = $orderLineItem->getProduct();
            }

            if ($product && $product->getId()) {
                try {
                    $this->createLog(
                        'Reloading product via repository for categories | productId: ' . $product->getId()
                        . ' | storeId: ' . $storeId
                    );

                    $product = $this->productRepository->getById($product->getId(), false, $storeId);
                    $productCategories = $this->getProductCategories($product, $storeId);

                    $this->createLog(
                        'Product categories collected | itemId: ' . $itemId
                        . ' | category count: ' . count($productCategories)
                    );
                } catch (\Exception $e) {
                    $this->createLog(
                        'Failed to reload product or collect categories | itemId: ' . $itemId
                        . ' | error: ' . $e->getMessage()
                    );
                    // Intentionally ignored to continue with available data.
                    unset($e);
                }
            } else {
                $this->createLog('No product available for category lookup | itemId: ' . $itemId);
            }
        } catch (\Exception $e) {
            $this->createLog(
                'Exception while loading product for categories | itemId: ' . $itemId
                . ' | error: ' . $e->getMessage()
            );
            // Intentionally ignored to continue with available data.
            unset($e);
        }

        $shippingEstimate = $estimatedShipDate;
        $promiseDate = '';

        $orderDate = $order->getCreatedAt();
        $orderTotal = $order->getGrandTotal();
        $orderSubtotal = $order->getSubtotal();
        $orderCurrency = $order->getOrderCurrencyCode();
        $customerFirstName = $order->getCustomerFirstname()
            ?: ($order->getBillingAddress() ? $order->getBillingAddress()->getFirstname() : '');
        $customerLastName = $order->getCustomerLastname()
            ?: ($order->getBillingAddress() ? $order->getBillingAddress()->getLastname() : '');
        $customerId = $order->getCustomerId();

        $this->createLog(
            'Order and customer details collected | orderId: ' . $orderId
            . ' | orderDate: ' . $orderDate
            . ' | orderTotal: ' . $orderTotal
            . ' | orderStatus: ' . $order->getStatus()
            . ' | orderState: ' . $order->getState()
            . ' | customerId: ' . ($customerId ?: 'guest')
        );

        $eventTime = new \DateTime();
        $eventTime->setTimestamp(time());
        $time = $eventTime->format('Y-m-d\TH:i:s');

        $isKlaviyoEnabled = $this->klaviyoScopeSetting->isEnabled();
        $privateApiKey = $this->klaviyoScopeSetting->getPrivateApiKey();

        $this->createLog(
            'Klaviyo configuration check | orderId: ' . $orderId
            . ' | isEnabled: ' . ($isKlaviyoEnabled ? 'yes' : 'no')
            . ' | hasPrivateApiKey: ' . ($privateApiKey ? 'yes' : 'no')
        );

        if ($isKlaviyoEnabled && $privateApiKey) {
            $this->createLog(
                'Building Klaviyo event payload | orderId: ' . $orderId
                . ' | itemId: ' . $itemId
                . ' | metric: missedESDnotify'
            );

            $eventData = [
                'data' => [
                    'type' => 'event',
                    'attributes' => [
                        'properties' => [
                            'OrderId' => $orderId,
                            'ProductId' => $productId,
                            'Name' => $lineItemName,
                            'Sku' => $sku,
                            'ProductUrl' => $productUrl,
                            'Quantity' => (float)$quantity,
                            'Price' => (float)$price,
                            'Total' => (float)$total,
                            'PromiseDate' => $promiseDate,
                            'ShippingEstimate' => $shippingEstimate,
                            'ProductCategories' => $productCategories,
                            'ParentProductInfo' => [
                                'ParentProductId' => $parentProductId,
                                'ParentSku' => $parentSku,
                            ],
                            'OrderInfo' => [
                                'OrderDate' => $orderDate,
                                'OrderTotal' => (float)$orderTotal,
                                'OrderSubtotal' => (float)$orderSubtotal,
                                'OrderCurrency' => $orderCurrency,
                                'OrderStatus' => $order->getStatus(),
                                'OrderState' => $order->getState(),
                            ],
                            'CustomerInfo' => [
                                'CustomerId' => $customerId,
                                'CustomerFirstName' => $customerFirstName,
                                'CustomerLastName' => $customerLastName,
                                'CustomerEmail' => $customerEmail,
                            ],
                        ],
                        'time' => $time,
                        'metric' => [
                            'data' => [
                                'type' => 'metric',
                                'attributes' => [
                                    'name' => 'missedESDnotify',
                                    'service' => 'magentotwo',
                                ],
                            ],
                        ],
                        'profile' => [
                            'data' => [
                                'type' => 'profile',
                                'attributes' => [
                                    'email' => $customerEmail,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $jsonData = json_encode($eventData);

            $klVersion = $this->klaviyoScopeSetting->getVersion();
            $m2Version = $this->productMetadata->getVersion();

            $url = 'https://a.klaviyo.com/api/events/';

            $this->createLog(
                'Sending Klaviyo API request | orderId: ' . $orderId
                . ' | itemId: ' . $itemId
                . ' | url: ' . $url
                . ' | payload size: ' . strlen($jsonData) . ' bytes'
            );

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_HTTPHEADER => [
                    'revision: 2023-08-15',
                    'Content-Type: application/json',
                    'accept: application/json',
                    'X-Klaviyo-User-Agent: magento2-klaviyo/' . $klVersion
                        . ' Magento2/' . $m2Version . ' PHP/' . phpversion(),
                    'Authorization: Klaviyo-API-Key ' . $privateApiKey,
                ],
            ]);

            $response = curl_exec($ch);
            $phpVersionHttpCode = version_compare(phpversion(), '5.5.0', '>') ? CURLINFO_RESPONSE_CODE : CURLINFO_HTTP_CODE;
            $httpCode = curl_getinfo($ch, $phpVersionHttpCode);
            $curlError = curl_error($ch);
            curl_close($ch);

            $this->createLog(
                'Klaviyo API response received | orderId: ' . $orderId
                . ' | itemId: ' . $itemId
                . ' | httpCode: ' . $httpCode
                . ' | curlError: ' . ($curlError ?: 'none')
                . ' | response: ' . (is_string($response) ? substr($response, 0, 500) : 'empty')
            );

            if ($httpCode == 202 || $httpCode == 200) {
                $this->createLog(
                    'Klaviyo API call succeeded | orderId: ' . $orderId
                    . ' | itemId: ' . $itemId
                    . ' | httpCode: ' . $httpCode
                );

                // Mark this order item as notified about ESD delay.
                $this->createLog(
                    'Updating esd_delay_notified flag in database | itemId: ' . $itemId
                    . ' | orderId: ' . $orderId
                    . ' | SKU: ' . $sku
                );

                try {
                    $orderLineItem->setData('esd_delay_notified', 1);
                    $this->orderItemResource->save($orderLineItem);

                    $this->createLog(
                        'ESD flag updated successfully | itemId: ' . $itemId
                        . ' | orderId: ' . $orderId
                        . ' | SKU: ' . $sku
                        . ' | productId: ' . $productId
                    );
                } catch (\Exception $e) {
                    $this->createLog(
                        'Error while updating ESD flag | itemId: ' . $itemId
                        . ' | orderId: ' . $orderId
                        . ' | SKU: ' . $sku
                        . ' | productId: ' . $productId
                        . ' | error: ' . $e->getMessage()
                    );
                }
            } else {
                $this->createLog(
                    'Klaviyo API call failed | orderId: ' . $orderId
                    . ' | itemId: ' . $itemId
                    . ' | httpCode: ' . $httpCode
                    . ' | curlError: ' . ($curlError ?: 'none')
                    . ' | esd_delay_notified flag NOT updated'
                );
            }
        } else {
            $this->createLog(
                'Klaviyo notification skipped: integration disabled or missing API key | orderId: ' . $orderId
                . ' | itemId: ' . $itemId
                . ' | isEnabled: ' . ($isKlaviyoEnabled ? 'yes' : 'no')
                . ' | hasPrivateApiKey: ' . ($privateApiKey ? 'yes' : 'no')
            );
        }

        $this->createLog(
            'triggerOrderItemsESDNotificationToKlaviyo completed | itemId: ' . $itemId
            . ' | orderId: ' . $orderId
        );
    }

    /**
     * Get order items that are not shipped and where current date > ESD start date.
     *
     * @return \Magento\Sales\Model\Order\Item[]
     */
    private function getMissedESDOrderItems()
    {
        $this->createLog('getMissedESDOrderItems started: building order item collection query');

        $collection = $this->orderItemCollectionFactory->create();

        // Only items that are not fully shipped
        $collection->getSelect()->where(
            '(qty_shipped IS NULL OR qty_shipped < qty_ordered)'
        );

        // Join sales_order to filter by order status = 'processing'
        $orderTable = $collection->getTable('sales_order');
        $collection->getSelect()->join(
            ['o' => $orderTable],
            'main_table.order_id = o.entity_id',
            []
        )->where('o.status = ?', 'processing');

        $this->createLog(
            'Order item collection query built | filters: unshipped items, order status=processing'
        );

        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $todayFormatted = $today->format('Y-m-d');

        $this->createLog('Executing collection query | today (UTC): ' . $todayFormatted);

        $result = [];
        $collectionSize = $collection->getSize();

        $this->createLog(
            'Collection query executed | total items in collection (before ESD filtering): ' . $collectionSize
        );

        $iterationIndex = 0;
        $skippedChildItems = 0;
        $skippedUnparseableEsd = 0;
        $skippedOutsideEsdWindow = 0;
        $matchedMissedEsd = 0;

        foreach ($collection as $item) {
            $iterationIndex++;
            $itemId = $item->getId();
            $orderId = $item->getOrderId();
            $sku = $item->getSku();

            $this->createLog(
                'Collection loop iteration #' . $iterationIndex
                . ' | itemId: ' . $itemId
                . ' | orderId: ' . $orderId
                . ' | SKU: ' . $sku
            );

            // Only consider top-level (visible) items to avoid configurable + child duplicates.
            if ($item->getParentItemId() !== null) {
                $this->createLog(
                    'Collection item skipped: child item (has parentItemId) | itemId: ' . $itemId
                    . ' | parentItemId: ' . $item->getParentItemId()
                );
                $skippedChildItems++;
                continue;
            }

            $this->createLog('Parsing ESD date range for itemId: ' . $itemId);

            $esdStart = $this->parseESDStartDate($item);
            $esdEnd   = $this->parseESDEndDate($item);

            if ($esdStart === null || $esdEnd === null) {
                $this->createLog(
                    'Collection item skipped: ESD range could not be parsed | itemId: ' . $itemId
                    . ' | orderId: ' . $orderId
                    . ' | SKU: ' . $sku
                );
                $skippedUnparseableEsd++;
                // If we cannot parse ESD range, log it and skip this item.
                continue;
            }

            $this->createLog(
                'ESD range parsed | itemId: ' . $itemId
                . ' | esdStart: ' . $esdStart->format('Y-m-d')
                . ' | esdEnd: ' . $esdEnd->format('Y-m-d')
                . ' | today: ' . $todayFormatted
            );

            // Item is "missed" only when today is strictly within its own ESD window.
            if ($today > $esdStart && $today < $esdEnd) {
                $this->createLog(
                    'Collection item matched missed ESD criteria | itemId: ' . $itemId
                    . ' | orderId: ' . $orderId
                    . ' | SKU: ' . $sku
                );
                $result[] = $item;
                $matchedMissedEsd++;
            } else {
                $this->createLog(
                    'Collection item skipped: today is outside ESD window | itemId: ' . $itemId
                    . ' | orderId: ' . $orderId
                    . ' | condition: today > esdStart AND today < esdEnd = false'
                );
                $skippedOutsideEsdWindow++;
            }
        }

        $this->createLog(
            'getMissedESDOrderItems completed | collection size: ' . $collectionSize
            . ' | iterations: ' . $iterationIndex
            . ' | skipped child items: ' . $skippedChildItems
            . ' | skipped unparseable ESD: ' . $skippedUnparseableEsd
            . ' | skipped outside ESD window: ' . $skippedOutsideEsdWindow
            . ' | matched missed ESD: ' . $matchedMissedEsd
            . ' | result count: ' . count($result)
        );

        return $result;
    }

    /**
     * Parse ESD start date from order item (pdp_line_item.shipping_estimate).
     *
     * Uses the same year-resolution logic as Dcw_IncstoreShipping::getESDMinMax().
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return \DateTimeImmutable|null
     */
    private function parseESDStartDate($item)
    {
        $itemId = $item->getId();
        $this->createLog('parseESDStartDate started | itemId: ' . $itemId);
		
		$pdplineitemObject = $this->decodeJsonObject($item->getPdpLineItem());
		
		$orderId = $item->getOrderId();

        $range = $this->getESDMinMax($pdplineitemObject, $item);

        if ($range['esdMin'] === '') {
            $this->createLog(
                'parseESDStartDate failed: esdMin is empty | itemId: ' . $itemId
            );
            return null;
        }

        $tz = new \DateTimeZone('UTC');
        $date = \DateTimeImmutable::createFromFormat('m/d/Y', $range['esdMin'], $tz);

        if ($date === false) {
            $this->createLog(
                'parseESDStartDate failed: invalid date format | itemId: ' . $itemId
                . ' | esdMin: ' . $range['esdMin']
            );
            return null;
        }

        $parsedDate = $date->setTime(0, 0, 0);

        $this->createLog(
            'parseESDStartDate succeeded | itemId: ' . $itemId
            . ' | esdStart: ' . $parsedDate->format('Y-m-d')
        );

        return $parsedDate;
    }

    /**
     * Parse ESD end date from order item (pdp_line_item.shipping_estimate).
     *
     * Uses the same year-resolution logic as Dcw_IncstoreShipping::getESDMinMax().
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return \DateTimeImmutable|null
     */
    private function parseESDEndDate($item)
    {
        $itemId = $item->getId();
        $this->createLog('parseESDEndDate started | itemId: ' . $itemId);
		
		$pdplineitemObject = $this->decodeJsonObject($item->getPdpLineItem());
		
		$orderId = $item->getOrderId();

        $range = $this->getESDMinMax($pdplineitemObject, $item);

        if ($range['esdMax'] === '') {
            $this->createLog(
                'parseESDEndDate failed: esdMax is empty | itemId: ' . $itemId
            );
            return null;
        }

        $tz = new \DateTimeZone('UTC');
        $date = \DateTimeImmutable::createFromFormat('m/d/Y', $range['esdMax'], $tz);

        if ($date === false) {
            $this->createLog(
                'parseESDEndDate failed: invalid date format | itemId: ' . $itemId
                . ' | esdMax: ' . $range['esdMax']
            );
            return null;
        }

        $parsedDate = $date->setTime(0, 0, 0);

        $this->createLog(
            'parseESDEndDate succeeded | itemId: ' . $itemId
            . ' | esdEnd: ' . $parsedDate->format('Y-m-d')
        );

        return $parsedDate;
    }

    /**
     * Compute ESD start/end dates (MM/DD/YYYY) from the shipping_estimate string.
     *
     * Mirrors logic from Dcw_IncstoreShipping::getESDMinMax().
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return array{esdMin:string,esdMax:string}
     */
    private function computeEsdRangeFromShippingEstimate($item)
    {
        $itemId = $item->getId();
        $esdMin = '';
        $esdMax = '';

        $pdpLineItem = $item->getData('pdp_line_item');

        if (!$pdpLineItem) {
            $this->createLog(
                'computeEsdRangeFromShippingEstimate: pdp_line_item is empty | itemId: ' . $itemId
            );
            return ['esdMin' => $esdMin, 'esdMax' => $esdMax];
        }

        $pdplineitemObject = json_decode($pdpLineItem ?? '');

        if ($pdplineitemObject === null || !isset($pdplineitemObject->shipping_estimate)) {
            $this->createLog(
                'computeEsdRangeFromShippingEstimate: invalid JSON or missing shipping_estimate | itemId: '
                . $itemId . ' | pdp_line_item length: ' . strlen($pdpLineItem)
            );
            return ['esdMin' => $esdMin, 'esdMax' => $esdMax];
        }

        $pattern = '/(\w{3} \d{1,2}) - (\w{3} \d{1,2})/';
        $singleDatePattern = '/(\w{3} \d{1,2})/';

        $currentMonth = date('M', strtotime($item->getCreatedAt()));
        $currentYear = date('Y', strtotime($item->getCreatedAt()));

        $minYear = '';
        $maxYear = '';

        $shippingEstimate = (string)$pdplineitemObject->shipping_estimate;

        $this->createLog(
            'computeEsdRangeFromShippingEstimate: parsing shipping_estimate | itemId: ' . $itemId
            . ' | shipping_estimate: ' . $shippingEstimate
            . ' | orderCreatedMonth: ' . $currentMonth
            . ' | orderCreatedYear: ' . $currentYear
        );

        if (preg_match($pattern, $shippingEstimate, $matches)) {
            $this->createLog(
                'computeEsdRangeFromShippingEstimate: matched date range pattern | itemId: ' . $itemId
            );

            $esdMinParts = explode(' ', $matches[1]);
            $esdMaxParts = explode(' ', $matches[2]);

            if (ucfirst($esdMinParts[0]) === 'Jan' && $currentMonth === 'Dec') {
                $minYear = (string)($currentYear + 1);
            }

            if ($currentMonth === ucfirst($esdMinParts[0])) {
                $minYear = (string)$currentYear;
            }

            $months = $this->getMonthsForEsd();

            if ($months[$currentMonth] > $months[ucfirst($esdMinParts[0])]) {
                $minYear = (string)($currentYear + 1);
            }

            if ($months[$currentMonth] > $months[ucfirst($esdMaxParts[0])]) {
                $maxYear = (string)($currentYear + 1);
            }

            if ($minYear === '') {
                $minYear = (string)$currentYear;
            }

            if ($maxYear === '') {
                $maxYear = (string)$currentYear;
            }

            $esdMin = date('m/d/Y', strtotime(ucfirst($esdMinParts[0]) . ' ' . ucfirst($esdMinParts[1]) . ' ' . $minYear));
            $esdMax = date('m/d/Y', strtotime(ucfirst($esdMaxParts[0]) . ' ' . ucfirst($esdMaxParts[1]) . ' ' . $maxYear));
        } elseif (preg_match($singleDatePattern, $shippingEstimate, $matches)) {
            $this->createLog(
                'computeEsdRangeFromShippingEstimate: matched single date pattern | itemId: ' . $itemId
            );

            $esdMinParts = explode(' ', $matches[1]);

            if (ucfirst($esdMinParts[0]) === 'Jan' && $currentMonth === 'Dec') {
                $minYear = (string)($currentYear + 1);
            }

            if ($currentMonth === ucfirst($esdMinParts[0])) {
                $minYear = (string)$currentYear;
            }

            $months = $this->getMonthsForEsd();

            if ($months[$currentMonth] > $months[ucfirst($esdMinParts[0])]) {
                $minYear = (string)($currentYear + 1);
            }

            if ($minYear === '') {
                $minYear = (string)$currentYear;
            }

            $esdMin = date('m/d/Y', strtotime(ucfirst($esdMinParts[0]) . ' ' . ucfirst($esdMinParts[1]) . ' ' . $minYear));
            $esdMax = $esdMin;
        } else {
            $this->createLog(
                'computeEsdRangeFromShippingEstimate: no date pattern matched | itemId: ' . $itemId
                . ' | shipping_estimate: ' . $shippingEstimate
            );
        }

        $this->createLog(
            'computeEsdRangeFromShippingEstimate completed | itemId: ' . $itemId
            . ' | esdMin: ' . ($esdMin ?: 'empty')
            . ' | esdMax: ' . ($esdMax ?: 'empty')
        );

        return ['esdMin' => $esdMin, 'esdMax' => $esdMax];
    }

    /**
     * Month map used for ESD year resolution logic.
     *
     * @return array<string,int>
     */
    private function getMonthsForEsd()
    {
        return [
            'Jan' => 1,
            'Feb' => 2,
            'Mar' => 3,
            'Apr' => 4,
            'May' => 5,
            'Jun' => 6,
            'Jul' => 7,
            'Aug' => 8,
            'Sep' => 9,
            'Oct' => 10,
            'Nov' => 11,
            'Dec' => 12,
        ];
    }

    /**
     * Get ESD start date string for logging (MM/DD/YYYY), using the same logic as computeEsdMinFromShippingEstimate().
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    private function getESDStartDate($item)
    {
        $itemId = $item->getId();
        $this->createLog('getESDStartDate started | itemId: ' . $itemId);
		
		$pdplineitemObject = $this->decodeJsonObject($item->getPdpLineItem());
		
		$orderId = $item->getOrderId();

        $range = $this->getESDMinMax($pdplineitemObject, $item);
		
        $esdStart = $range['esdMin'];

        $this->createLog(
            'getESDStartDate completed | itemId: ' . $itemId
            . ' | esdStart: ' . ($esdStart ?: 'empty')
        );

        return $esdStart;
    }

    /**
     * Write a log message to var/log/CheckMissedESD.log
     *
     * @param string $msg
     * @return void
     */
    public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/CheckMissedESD.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
	public function getESDMinMax($pdplineitemObject, $item)
    {
        try {
            return $this->resolveESDMinMax($pdplineitemObject, $item);
        } catch (\Throwable $e) {
			$this->createLog(
            'exception: ' . $e->getMessage()
            . ' | shipping_estimate: ' . $pdplineitemObject->shipping_estimate
        );

            return ['esdMin' => '', 'esdMax' => ''];
        }
    }

    public function resolveESDMinMax($pdplineitemObject, $item): array
    {
        $esdMinMax = ['esdMin' => '', 'esdMax' => ''];

        if ($pdplineitemObject === null) {
            return $esdMinMax;
        }

        $createdAtTimestamp = $this->parseTimestamp((string)$item->getCreatedAt());
        if ($createdAtTimestamp === null) {
			$this->createLog(
                'invalid item created_at | created_at: ' . (string)$item->getCreatedAt()
            );
            return $esdMinMax;
        }

        if (!isset($pdplineitemObject->shipping_estimate)) {
            return $esdMinMax;
        }

        $shippingEstimate = (string)$pdplineitemObject->shipping_estimate;
        $currentMonth = date('M', $createdAtTimestamp);
        $currentYear = (int)date('Y', $createdAtTimestamp);
        $rangePattern = $this->getEsdRangePattern();
        $singleDatePattern = $this->getEsdSingleDatePattern();

        if (str_contains($shippingEstimate, '-')) {
            if (!preg_match($rangePattern, $shippingEstimate, $matches)) {
                
                return $esdMinMax;
            }

            [$esdMinMonth, $esdMinDay] = $this->parseEsdMonthDay($matches[1]);
            [$esdMaxMonth, $esdMaxDay] = $this->parseEsdMonthDay($matches[2]);

            if (!$this->isValidEsdMonthDay($esdMinMonth, $esdMinDay) || !$this->isValidEsdMonthDay($esdMaxMonth, $esdMaxDay)) {
                
                return $esdMinMax;
            }

            $minYear = $this->resolveEsdYear($currentMonth, $currentYear, $esdMinMonth);
            $maxYear = $this->resolveEsdYear($currentMonth, $currentYear, $esdMaxMonth);

            $esdMinMax['esdMin'] = $this->formatEsdDate(
                $esdMinMonth . ' ' . $esdMinDay . ' ' . $minYear,
                $item,
                $shippingEstimate,
                'esd_min'
            );
            $esdMinMax['esdMax'] = $this->formatEsdDate(
                $esdMaxMonth . ' ' . $esdMaxDay . ' ' . $maxYear,
                $item,
                $shippingEstimate,
                'esd_max'
            );

            return $esdMinMax;
        }

        if (preg_match($singleDatePattern, $shippingEstimate, $matches)) {
            [$esdMinMonth, $esdMinDay] = $this->parseEsdMonthDay($matches[1]);

            if (!$this->isValidEsdMonthDay($esdMinMonth, $esdMinDay)) {
                
                return $esdMinMax;
            }

            $minYear = $this->resolveEsdYear($currentMonth, $currentYear, $esdMinMonth);
            $esdMinMax['esdMin'] = $this->formatEsdDate(
                $esdMinMonth . ' ' . $esdMinDay . ' ' . $minYear,
                $item,
                $shippingEstimate,
                'esd_min'
            );
        }

        return $esdMinMax;
    }
    private function isValidEsdMonth(string $month): bool
        {
            return isset($this->getMonths()[$month]);
        }
        private function isValidEsdMonthDay(string $month, string $day): bool
        {
            if (!$this->isValidEsdMonth($month)) {
                return false;
            }
    
            $dayInt = (int)$day;
    
            return $dayInt >= 1 && $dayInt <= 31;
        }
        private function normalizeEsdMonth(string $month): string
        {
            if ($month === '') {
                return '';
            }
    
            $normalized = ucfirst(strtolower($month));
    
            return $this->getEsdFullMonthNames()[$normalized] ?? $normalized;
        }
        private function getEsdFullMonthNames(): array
        {
            return [
                'January' => 'Jan',
                'February' => 'Feb',
                'March' => 'Mar',
                'April' => 'Apr',
                'May' => 'May',
                'June' => 'Jun',
                'July' => 'Jul',
                'August' => 'Aug',
                'September' => 'Sep',
                'October' => 'Oct',
                'November' => 'Nov',
                'December' => 'Dec',
            ];
        }
        private function parseEsdMonthDay(string $matchedDate): array
        {
            if (!preg_match('/^([A-Za-z]+)\s*(\d{1,2})(?!\d)/', trim($matchedDate), $matches)) {
                return ['', ''];
            }
    
            return [$this->normalizeEsdMonth($matches[1]), $matches[2]];
        }
        private function getEsdMonthDayPatternFragment(): string
        {
            $monthNames = array_keys($this->getMonths());
            foreach ($this->getEsdFullMonthNames() as $fullName => $abbreviation) {
                if ($fullName !== $abbreviation) {
                    $monthNames[] = $fullName;
                }
            }
    
            usort($monthNames, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
    
            $parts = [];
            foreach ($monthNames as $monthName) {
                $parts[] = $monthName . '\s*\d{1,2}(?!\d)';
            }
    
            return implode('|', $parts);
        }
        private function getEsdRangePattern(): string
        {
            $monthDay = $this->getEsdMonthDayPatternFragment();
    
            return '/(' . $monthDay . ')\s*-\s*(' . $monthDay . ')/i';
        }
    
        private function getEsdSingleDatePattern(): string
        {
            return '/(' . $this->getEsdMonthDayPatternFragment() . ')/i';
        }
        private function parseTimestamp(string $dateString): ?int
        {
            if ($dateString === '') {
                return null;
            }
    
            $timestamp = strtotime($dateString);
    
            return $timestamp !== false ? $timestamp : null;
        }
        private function resolveEsdYear(string $currentMonth, int $currentYear, string $esdMonth): int
        {
            $months = $this->getMonths();
            $year = $currentYear;
    
            if ($esdMonth === 'Jan' && $currentMonth === 'Dec') {
                $year = $currentYear + 1;
            }
    
            if ($currentMonth === $esdMonth) {
                $year = $currentYear;
            }
    
            if (isset($months[$esdMonth], $months[$currentMonth]) && $months[$currentMonth] > $months[$esdMonth]) {
                $year = $currentYear + 1;
            }
    
            return $year;
        }
        private function formatEsdDate(
            string $dateString,
            $item,
            string $shippingEstimate,
            string $field
        ): string {
            $timestamp = $this->parseTimestamp($dateString);
            if ($timestamp === null) {
                
    
                return '';
            }
    
            try {
                return date('m/d/Y', $timestamp);
            } catch (\Throwable $e) {
                
    
                return '';
            }
        }
        private function logPluginError(OrderInterface $order, string $context, \Throwable $e): void
        {
            $message = sprintf(
                'Order attributes skipped (%s) | order_id=%s | increment_id=%s | error=%s',
                $context,
                (string)$order->getEntityId(),
                (string)$order->getIncrementId(),
                $e->getMessage()
            );
            $this->createLog($message);
        }
        private function decodeJsonObject(?string $json): ?\stdClass
        {
            if ($json === null || trim($json) === '') {
                return null;
            }
    
            $decoded = json_decode($json);
    
            return $decoded instanceof \stdClass ? $decoded : null;
        }
        private function getJsonValue(?\stdClass $object, string $property, $default = '')
        {
            if ($object === null || !property_exists($object, $property)) {
                return $default;
            }
    
            return $object->{$property} ?? $default;
        }
		public function getMonths()
    {
        return [
            'Jan' => 1,
            'Feb' => 2,
            'Mar' => 3,
            'Apr' => 4,
            'May' => 5,
            'Jun' => 6,
            'Jul' => 7,
            'Aug' => 8,
            'Sep' => 9,
            'Oct' => 10,
            'Nov' => 11,
            'Dec' => 12,
        ];
    }
        private function logEsdParseError(OrderInterface $order, $item, string $reason, array $context = []): void
        {
            $message = sprintf(
                'ESD skipped (%s) | order_id=%s | increment_id=%s | item_id=%s | sku=%s | %s',
                $reason,
                (string)$order->getEntityId(),
                (string)$order->getIncrementId(),
                (string)$item->getItemId(),
                (string)$item->getSku(),
                http_build_query($context, '', ' | ')
            );
            $this->createLog($message);
        }
	}