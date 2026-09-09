<?php
namespace Dcw\BackorderNotification\Service;

use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Klaviyo\Reclaim\Helper\ScopeSetting as KlaviyoScopeSetting;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Sales\Model\ResourceModel\Order\Item as OrderItemResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class KlaviyoNotificationService
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var KlaviyoScopeSetting
     */
    protected $klaviyoScopeSetting;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Configurable
     */
    protected $configurableProductType;

    /**
     * @var OrderItemResource
     */
    protected $orderItemResource;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Zend_Log|null
     */
    protected $customLogger = null;

    const XML_PATH_ENABLED = 'backorder_notification/general/enabled';

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param KlaviyoScopeSetting $klaviyoScopeSetting
     * @param ProductMetadataInterface $productMetadata
     * @param StoreManagerInterface $storeManager
     * @param Configurable $configurableProductType
     * @param OrderItemResource $orderItemResource
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        KlaviyoScopeSetting $klaviyoScopeSetting,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        Configurable $configurableProductType,
        OrderItemResource $orderItemResource,
        ScopeConfigInterface $scopeConfig
    ) {
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
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get custom logger instance for BackOrderNotification.log
     *
     * @return \Zend_Log
     */
    public function getCustomLogger()
    {
        if ($this->customLogger === null) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/BackOrderNotification.log');
            $this->customLogger = new \Zend_Log();
            $this->customLogger->addWriter($writer);
        }
        return $this->customLogger;
    }

    /**
     * Get current promise date from order item's pdp_line_item
     *
     * @param OrderItem $orderItem
     * @return string
     */
    public function getCurrentPromiseDateFromItem(OrderItem $orderItem)
    {
        try {
            $pdpLineItemJson = $orderItem->getData('pdp_line_item');
            if ($pdpLineItemJson) {
                $pdpData = json_decode($pdpLineItemJson, true);
                if (is_array($pdpData) && isset($pdpData['promise_date'])) {
                    return (string)$pdpData['promise_date'];
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        return '';
    }

    /**
     * Get shipping estimate from pdp_line_item
     *
     * @param OrderItem $orderLineItem
     * @return string
     */
    public function getShippingEstimateFromPdpLineItem(OrderItem $orderLineItem)
    {
        try {
            $pdpLineItemJson = $orderLineItem->getData('pdp_line_item');
            if ($pdpLineItemJson) {
                $pdpData = json_decode($pdpLineItemJson, true);
                if (is_array($pdpData) && isset($pdpData['shipping_estimate'])) {
                    return (string)$pdpData['shipping_estimate'];
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        return '';
    }

    /**
     * Get product categories as array of names
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $storeId
     * @return array
     */
    public function getProductCategories($product, $storeId)
    {
        $categoryNames = [];
        try {
            $categoryIds = $product->getCategoryIds();
            if (!empty($categoryIds)) {
                foreach ($categoryIds as $categoryId) {
                    try {
                        $category = $this->categoryRepository->get($categoryId, $storeId);
                        if ($category && $category->getName()) {
                            $categoryNames[] = $category->getName();
                        }
                    } catch (\Exception $e) {
                        // Continue with next category
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        return $categoryNames;
    }

    /**
     * Trigger backorder notification to Klaviyo
     *
     * @param OrderItem $orderLineItem
     * @param string $promiseDate
     * @param string $currentPromiseDate
     * @return void
     */
    public function triggerBackorderNotificationToKlaviyo(OrderItem $orderLineItem, $promiseDate, $currentPromiseDate = '')
    {
        $order = $orderLineItem->getOrder();
        $customerEmail = $order->getCustomerEmail();
        $orderId = $order->getIncrementId();
        $lineItemName = $orderLineItem->getName();
        
        if (empty($customerEmail)) {
            $this->getCustomLogger()->warn('BackorderNotification: No customer email, cannot send to Klaviyo - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId());
            return;
        }
        
        // Check if item has a parent (configurable product scenario)
        $parentItem = $orderLineItem->getParentItem();
        $parentProductId = null;
        $parentSku = null;
        $productUrl = "";
        $storeId = $order->getStoreId();
        $currentProductId = $orderLineItem->getProductId();
        $currentProductType = $orderLineItem->getProductType();
        
        // Determine which product to use for URL (parent if exists, otherwise current item)
        $productForUrl = null;
        
        // Case 1: Item has a parent item (simple product with configurable parent)
        if ($parentItem) {
            try {
                $parentProductId = $parentItem->getProductId();
                
                // Load parent product from repository to get the actual product SKU
                if ($parentProductId) {
                    try {
                        $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId);
                        $parentSku = $parentProduct->getSku();
                        $productForUrl = $parentProduct;
                    } catch (\Exception $e) {
                        $this->getCustomLogger()->warn('BackorderNotification: Failed to load parent product from repository - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Parent Product ID: ' . $parentProductId . ' | Error: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $this->getCustomLogger()->warn('BackorderNotification: Error getting parent product info - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Error: ' . $e->getMessage());
            }
        } 
        // Case 2: Item itself is a configurable product (it IS the parent)
        elseif ($currentProductType === 'configurable' && $currentProductId) {
            $parentProductId = $currentProductId;
            
            // Load the configurable product from repository to get the actual product SKU
            try {
                $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId);
                $parentSku = $parentProduct->getSku();
                $productForUrl = $parentProduct;
            } catch (\Exception $e) {
                $this->getCustomLogger()->warn('BackorderNotification: Failed to load configurable product - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Parent Product ID: ' . $parentProductId . ' | Error: ' . $e->getMessage());
            }
        }
        // Case 3: Fallback - Check if this is a simple product that belongs to a configurable product
        else {
            try {
                if ($currentProductId) {
                    $parentIds = $this->configurableProductType->getParentIdsByChild($currentProductId);
                    if (!empty($parentIds) && isset($parentIds[0])) {
                        $parentProductId = $parentIds[0];
                        
                        // Load parent product to get SKU and URL
                        try {
                            $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId);
                            $parentSku = $parentProduct->getSku();
                            $productForUrl = $parentProduct;
                        } catch (\Exception $e) {
                            $this->getCustomLogger()->warn('BackorderNotification: Failed to load parent product via configurable lookup - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Parent Product ID: ' . $parentProductId . ' | Error: ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->getCustomLogger()->warn('BackorderNotification: Error checking configurable parent relationship - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Error: ' . $e->getMessage());
            }
        }
        
        // If no parent or product not loaded, get from current item's product
        if (!$productForUrl) {
            try {
                $currentProductId = $orderLineItem->getProductId();
                if ($currentProductId) {
                    $productForUrl = $this->productRepository->getById($currentProductId, false, $storeId);
                }
            } catch (\Exception $e) {
                $this->getCustomLogger()->warn('BackorderNotification: Failed to load current item product - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Error: ' . $e->getMessage());
            }
        }
        
        // Get product URL directly from the product object
        if ($productForUrl && $productForUrl->getId()) {
            $productUrl = $productForUrl->getProductUrl();
        }
        
        $productId = $orderLineItem->getProductId();
        $sku = $orderLineItem->getSku();
        $quantity = $orderLineItem->getQtyOrdered();
        $price = $orderLineItem->getPrice();
        $total = $orderLineItem->getRowTotal();
        
        // Get product for additional data
        $product = null;
        $productCategories = [];
        
        try {
            // Use parent product if available, otherwise use current item's product
            if ($parentItem) {
                $product = $parentItem->getProduct();
            } else {
                $product = $orderLineItem->getProduct();
            }
            
            // Load full product if needed
            if ($product && $product->getId()) {
                try {
                    $product = $this->productRepository->getById($product->getId(), false, $storeId);
                    $productCategories = $this->getProductCategories($product, $storeId);
                } catch (\Exception $e) {
                    // Silent fail, continue with available data
                }
            }
        } catch (\Exception $e) {
            // Silent fail, continue with available data
        }
        
        // Get shipping estimate from pdp_line_item
        $shippingEstimate = $this->getShippingEstimateFromPdpLineItem($orderLineItem);
        
        // Order-level information
        $orderDate = $order->getCreatedAt();
        $orderTotal = $order->getGrandTotal();
        $orderSubtotal = $order->getSubtotal();
        $orderCurrency = $order->getOrderCurrencyCode();
        $customerFirstName = $order->getCustomerFirstname() ?: ($order->getBillingAddress() ? $order->getBillingAddress()->getFirstname() : '');
        $customerLastName = $order->getCustomerLastname() ?: ($order->getBillingAddress() ? $order->getBillingAddress()->getLastname() : '');
        $customerId = $order->getCustomerId();
        
        // Use same time format as Klaviyo SDK (Y-m-d\TH:i:s instead of ISO 8601)
        $eventTime = new \DateTime();
        $eventTime->setTimestamp(time());
        $time = $eventTime->format('Y-m-d\TH:i:s');

        $isKlaviyoEnabled = $this->klaviyoScopeSetting->isEnabled();
        $privateApiKey = $this->klaviyoScopeSetting->getPrivateApiKey();

        if ($isKlaviyoEnabled && $privateApiKey) {
            
            // Prepare event data according to Klaviyo Events API v3 format (matching Klaviyo SDK structure)
            $eventData = [
                'data' => [
                    'type' => 'event',
                    'attributes' => [
                        'properties' => [
                            // Line Item Information
                            'OrderId' => $orderId,
                            'ProductId' => $productId,
                            'Name' => $lineItemName,
                            'Sku' => $sku,
                            'ProductUrl' => $productUrl,
                            'Quantity' => (float)$quantity,
                            'Price' => (float)$price,
                            'Total' => (float)$total,
                            
                            // Promise Date Information
                            'PromiseDate' => $promiseDate,
                            'ShippingEstimate' => $shippingEstimate,
                            
                            // Product Categories
                            'ProductCategories' => $productCategories,
                            
                            // Parent Product Information (for configurable products)
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
                                    'name' => 'BackorderNotification',
                                    'service' => 'magentotwo'
                                ]
                            ]
                        ],
                        'profile' => [
                            'data' => [
                                'type' => 'profile',
                                'attributes' => [
                                    'email' => $customerEmail
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $jsonData = json_encode($eventData);
            
            // Get Klaviyo module version for User-Agent header
            $klVersion = $this->klaviyoScopeSetting->getVersion();
            $m2Version = $this->productMetadata->getVersion();
            
            // Make API call to Klaviyo - matching Klaviyo SDK implementation
            $url = 'https://a.klaviyo.com/api/events/';
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_HTTPHEADER => [
                    'revision: 2023-08-15', // Match Klaviyo SDK revision
                    'Content-Type: application/json',
                    'accept: application/json',
                    'X-Klaviyo-User-Agent: magento2-klaviyo/' . $klVersion . ' Magento2/' . $m2Version . ' PHP/' . phpversion(),
                    'Authorization: Klaviyo-API-Key ' . $privateApiKey
                ]
            ]);
            
            $response = curl_exec($ch);
            $phpVersionHttpCode = version_compare(phpversion(), '5.5.0', '>') ? CURLINFO_RESPONSE_CODE : CURLINFO_HTTP_CODE;
            $httpCode = curl_getinfo($ch, $phpVersionHttpCode);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log success or errors
            if ($httpCode == 202 || $httpCode == 200) {
                $this->getCustomLogger()->info('BackorderNotification: Klaviyo API call successful - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Promise Date Before: ' . $currentPromiseDate . ' | Promise Date After: ' . $promiseDate . ' | HTTP Code: ' . $httpCode);
            } else {
                $this->getCustomLogger()->err('BackorderNotification: Klaviyo API Error - HTTP Code: ' . $httpCode . ' | Response: ' . $response . ' | CURL Error: ' . $curlError . ' | Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Customer Email: ' . $customerEmail . ' | Promise Date Before: ' . $currentPromiseDate . ' | Promise Date After: ' . $promiseDate);
            }
        } else {
            $this->getCustomLogger()->warn('BackorderNotification: Klaviyo not enabled or no API key - Order ID: ' . $orderId . ' | Item ID: ' . $orderLineItem->getId() . ' | Klaviyo Enabled: ' . ($isKlaviyoEnabled ? 'Yes' : 'No') . ' | Has API Key: ' . (!empty($privateApiKey) ? 'Yes' : 'No'));
        }
    }
}

