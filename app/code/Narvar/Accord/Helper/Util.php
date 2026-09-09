<?php

namespace Narvar\Accord\Helper;

use Narvar\Accord\Config\MagentoConfig as MagentoConfig;
use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\RepositoryHelper;
use Narvar\Accord\Helper\AccordException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ProductMetadataInterface;
use Narvar\Accord\Logger\Logger;

class Util
{

    private $magentoConfig;

    private $constants;

    private $logger;

    private $repositoryHelper;

    private $searchCriteriaBuilder;

    private $productMetadata;
	
	protected $customLoggers;

    public function __construct(
        MagentoConfig $magentoConfig,
        Constants $constants,
        CustomLogger $logger,
        RepositoryHelper $repositoryHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductMetadataInterface $productMetadata,
		Logger $customLoggers
    ) {
        $this->magentoConfig = $magentoConfig;
        $this->constants     = $constants->getConstants();
        $this->logger            = $logger;
        $this->repositoryHelper = $repositoryHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productMetadata = $productMetadata;
		$this->customLoggers = $customLoggers;
    }

    /**
     * Method to push data to narvar
     *
     * @param $key  Narvar auth key.
     * @param $data Message to be hashed.
     *
     * @return string
     */
    public function createHmacKey($key, $data)
    {
        /*
            Function:
            hash_hmac ( string $algo , string $data , string $key [, bool $raw_output = FALSE ] ) : string

            Parameters:
            algo
            Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
            See hash_hmac_algos() for a list of supported algorithms.

            data
            Message to be hashed.

            key
            Shared secret key used for generating the HMAC variant of the message digest.

            raw_output
            When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
        */

        return base64_encode(hash_hmac("sha256", $data, $key, true));
    }


    public function getRetailerMoniker($storeId)
    {
        return $this->magentoConfig->get(
            $this->constants['RETAILER_MONIKER'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
    }

    public function isNarvarAccordEnabled($storeId)
    {
        return $this->magentoConfig->get(
            $this->constants['ACCORD_ENABLED'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
    }

    public function getAuthKey($storeId)
    {
        $authKey = $this->magentoConfig->get(
            $this->constants['AUTH_KEY'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
        if (empty($authKey)) {
            $this->logger->debug(
                'Narvar Auth Handshake not done - auth key missing',
                $storeId
            );
            throw new AccordException('Narvar Auth Handshake not done');
        }
        return $authKey;
    }

    public function getTimezone($storeId)
    {
        $timeZoneKey = 'general/locale/timezone';
        $scope = $this->constants['STORE_SCOPE'];
        return $this->magentoConfig->getConfigValue($timeZoneKey, $storeId, $scope);
    }

    public function getCheckoutLocale($storeId)
    {
        $localeConfigKey = 'general/locale/code';
        $scope = $this->constants['STORE_SCOPE'];
        return $this->magentoConfig->getConfigValue($localeConfigKey, $storeId, $scope);
    }

    public function handleException($ex, $orderId, $storeId, $eventName)
    {
        $message = 'Exception : ' . $ex->getMessage() . ' occured in Order Plugin';
        $errorLog = [];
        $errorLog['store_id'] = $storeId;
        $errorLog['event_name'] = $eventName;
        $errorLog['order_id'] = $orderId;
        $errorLog['milestone'] = 'end';
        $errorLog['message'] = $message;
        $this->logger->error(json_encode($errorLog), $storeId);
    }

    public function logMetadata($orderId, $storeId, $eventName, $milestone)
    {
        $loggingMetadata = [];
        $loggingMetadata['event_name'] = $eventName;
        $loggingMetadata['order_id']   = $orderId;
        $loggingMetadata['store_id']   = $storeId;
        $loggingMetadata['milestone'] = $milestone;
        $this->logger->info(json_encode($loggingMetadata));
    }

    public function getNarvarOrderObject($order, $eventName)
    {
        $orderData = $order->getData();
        $narvarOrderObject = $orderData;
        $storeId = $order->getStoreId();
        $this->logger->debug('getNarvarOrderObject', $storeId);
		$this->customLoggers->info('OrderID: ' . $order->getIncrementId());
        try {
            $narvarOrderObject['event_name']            = $eventName;

            $billingAddress = $order->getBillingAddress();
            $billingRegionData = null;
            if (!is_null($billingAddress)) {
                $regionId = $billingAddress->getRegionId();
                if (!is_null($regionId)) {
                    $billingRegionData = $this->repositoryHelper->getRegionById($regionId);
                }
                $billingAddress = $billingAddress->getData();
            }
            $narvarOrderObject['billing_address']       = $billingAddress;
            $narvarOrderObject['billing_region_data']   = $billingRegionData;
            $this->logger->debug('getNarvarOrderObject - billingAddress', $storeId);

            $shippingAddress = $order->getShippingAddress();
            $shippingRegionData = null;
            if (!is_null($shippingAddress)) {
                $regionId = $shippingAddress->getRegionId();
                if (!is_null($regionId)) {
                    $shippingRegionData = $this->repositoryHelper->getRegionById($regionId);
                }
                $shippingAddress = $shippingAddress->getData();
            }
            $narvarOrderObject['shipping_address']      = $shippingAddress;
            $narvarOrderObject['shipping_region_data']  = $shippingRegionData;
            $this->logger->debug('getNarvarOrderObject - shippingAddress', $storeId);

            $customerId = $order->getCustomerId();
            $customer = $this->repositoryHelper->getCustomer($customerId);
            $narvarOrderObject['customer']              = $customer->getData();
            $this->logger->debug('getNarvarOrderObject - customer', $storeId);

            $narvarOrderObject['timezone']              = $this->getTimezone($storeId);
            $narvarOrderObject['checkout_locale']       = $this->getCheckoutLocale($storeId);
            $this->logger->debug('getNarvarOrderObject - checkoutLocale', $storeId);

            $orderItems = $this->getItems($order->getItems(), $storeId);
            $narvarOrderObject['items']                 = $orderItems;
            $this->logger->debug('getNarvarOrderObject - orderItems', $storeId);
            $narvarOrderObject['customer_group']        = $this->repositoryHelper
                                                            ->getCustomerGroup($customer->getGroupId())->getCode();
            $this->logger->debug('getNarvarOrderObject - customerGroup', $storeId);
        } catch (\Exception $ex) {
            $this->handleException($ex, '', '', '');
        } finally {
            return $narvarOrderObject;
        }
    }

    public function getSearchCriteriaByOrderId($orderId)
    {
        return $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
    }

    public function getOrderShipments($order)
    {
        $shipmentSearchCriteria = $this->getSearchCriteriaByOrderId($order->getEntityId());
        return $this->repositoryHelper->getShipmentDataBySearchCriteria($shipmentSearchCriteria);
    }

    public function getShipmentData($shipment, $storeId)
    {
        $shipmentData  = $shipment->getData();
        $shipmentItems = [];
        foreach ($shipment->getItemsCollection() as $item) {
            $shipmentItem  = $item->getData();
            $product = $this->getProductFromItem($item);
            $shipmentItem['product']['type_id'] = $product->getTypeId();
            $shipmentItems[] = $shipmentItem;
        }
        $shipmentData['items'] = $shipmentItems;
        $trackingInfo = [];
        $tracks = $shipment->getTracksCollection()->addFieldToFilter(
            'parent_id',
            array('eq' => $shipment->getId())
        );
        foreach ($tracks as $trackitem) {
            $trackingInfo[] = $trackitem->getData();
        }
        $shipmentData['tracks'] = $trackingInfo;
        $shipmentData['source_data'] = $this->getSourceData($shipment);
        return $shipmentData;
    }

    public function getSourceData($shipment)
    {
        if (!(strpos($this->getMagentoVersion(), '2.2') === 0)) {
            $extensionAttributes = $shipment->getExtensionAttributes();
            if (
                !is_null($extensionAttributes)
                && is_object($extensionAttributes)
                && method_exists($extensionAttributes, 'getSourceCode')
            ) {
                $sourceCode = $extensionAttributes->getSourceCode();
                if (!is_null($sourceCode)) {
                    return $this->repositoryHelper->getSourceData($sourceCode);
                }
            }
        }
        return null;
    }

    public function getOrderInvoices($order)
    {
        $invoiceSearchCriteria = $this->getSearchCriteriaByOrderId($order->getEntityId());
        return $this->repositoryHelper->getInvoiceDataBySearchCriteria($invoiceSearchCriteria);
    }

    public function getInvoiceData($invoice, $storeId)
    {
        $invoiceData  = $invoice->getData();
        $invoiceItems = [];
        foreach ($invoice->getItemsCollection() as $item) {
            $invoiceItems[] = $item->getData();
        }
        $invoiceData['items'] = $invoiceItems;
        return $invoiceData;
    }

    public function getItems($items, $storeId)
    {
        $orderItems = [];
        try {
            foreach ($items as $item) {
                $tmpArray  = $item->getData();
                $this->logger->debug('getItems-getData', $storeId);
                try{
                    $product = $this->getProductFromItem($item);
					$productOptions = $item->getProductOptions();
                    $this->logger->debug('getProductFromItem', $storeId);
                    $tmpArray['product_options'] = $item->getProductOptions();
                    $this->logger->debug('getProductOptions', $storeId);
                    $tmpArray['product']         = $this->getProductData($product, $storeId);
                    $this->logger->debug('getProductData', $storeId);
                    $tmpArray['url']             = $this->getProductUrls($product, $storeId, $productOptions);
                    $this->logger->debug('getProductUrls', $storeId);
                    $tmpArray['categories']      = $this->getProductCategories($product, $storeId);
                    $this->logger->debug('getProductCategories', $storeId);
                } catch (\Exception $ex) {
                    $this->handleException($ex, '', '', '');
                } finally {
                    $orderItems[] = $tmpArray;
                }
            }
        } catch (\Exception $ex) {
            $this->handleException($ex, '', '', '');
        } finally {
            return $orderItems;
        }
    }

    public function getProductFromItem($item)
    {
        $productId = $item->getProductId();
        return $this->repositoryHelper->getProduct($productId);
    }

    public function getProductUrls($product, $storeId, $productOptions)
    {
        $urls = [];
		$main_configurable_product_image='';
	    $configurable_product_image='';
	    $configurable_product_url='';
		if(is_array($productOptions)){
		  if(array_key_exists("additional_options",$productOptions)){
			 $additional_options=$productOptions['additional_options'];
			 if(array_key_exists("main_configurable_product_image",$additional_options)){
				$main_configurable_product_image=$additional_options['main_configurable_product_image']['value'];
			 }
			 if(array_key_exists("configurable_product_image",$additional_options)){
				$configurable_product_image=$additional_options['configurable_product_image']['value'];
			 }
			 if(array_key_exists("configurable_product_url",$additional_options)){
				$configurable_product_url=$additional_options['configurable_product_url']['value'];
			 }
		  }
	  }
        try{
            $store   = $this->repositoryHelper->getStore($storeId);
            $this->logger->debug('getStore', $storeId);
			if($configurable_product_image){
				$urls['image'] = $configurable_product_image;
				$this->customLoggers->info('Configurable Product Image - SKU: ' . $product->getSku() . ', Image: ' . $configurable_product_image);
                $this->customLoggers->info('Main Configurable Product Image - SKU: ' . $product->getSku() . ', Image: ' . $main_configurable_product_image);
			}elseif(!empty($product->getImage())) {
                $urls['image'] = $store->getBaseUrl('media') . 'catalog/product' . $product->getImage();
				$imageUrl=$store->getBaseUrl('media') . 'catalog/product' . $product->getImage();
				$this->customLoggers->info('Product Image Found - SKU: ' . $product->getSku() . ', Image URL: ' . $imageUrl);
            } else {
                $urls['image'] = '';
				$this->customLoggers->info('No image found for SKU: ' . $product->getSku());
            }
            $this->logger->debug('getImage', $storeId);
            $urls['item'] = $product->getProductUrl();
            $this->logger->debug('getProductUrl', $storeId);
        } catch (\Exception $ex) {
            $this->handleException($ex, '', '', '');
        } finally {
            return $urls;
        }
    }

    public function getProductCategories($product, $storeId)
    {
        $categoryIds = $product->getCategoryIds();
        $this->logger->debug('getCategoryIds', $storeId);
        $categoryNames = [];
        try{
            if (!is_null($categoryIds) && is_array($categoryIds) && !empty($categoryIds)) {
                $categories = $this->repositoryHelper->getCategories($categoryIds);
                foreach ($categories as $category) {
                    try{
                        $categoryNames[] = $category->getName();
                    } catch (\Exception $ex) {
                        $this->handleException($ex, '', '', '');
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->handleException($ex, '', '', '');
        } finally {
            return $categoryNames;
        }
    }

    public function getProductData($product, $storeId)
    {
        $productData = $product->getData();
        $this->logger->debug('productData', $storeId);
        $attributes = $product->getCustomAttributes();
        $this->logger->debug('getCustomAttributes', $storeId);
        try {
            if (!is_null($attributes) && is_array($attributes) && !empty($attributes)) {
                foreach ($attributes as $attribute) {
                    try {
                        if (!is_null($attribute) && !is_null($attribute->getAttributeCode())) {
                            $productData[$attribute->getAttributeCode() . '_raw'] = $product->getResource()
                            ->getAttributeRawValue($product->getId(), $attribute->getAttributeCode(), $storeId);
                            $productData[$attribute->getAttributeCode()] = $product->getResource()
                            ->getAttribute($attribute->getAttributeCode())->getFrontend()->getValue($product);
                        }
                    } catch (\Exception $ex) {
                        $this->logger->debug('unable to fetch attribute', $storeId);
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->handleException($ex, '', '', '');
        } finally {
            return $productData;
        }
    }


    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }
    
    function utf8ize($d) {
        if ($d) {
            if (is_array($d) || is_object($d)) {
                foreach ($d as &$v) $v = $this->utf8ize($v);
            } else {
                $enc   = mb_detect_encoding($d);
                if ($enc) {
                    return mb_convert_encoding($d, 'UTF-8', $enc);
                } else {
                    return '';
                }
            }
        }
        return $d;
    }

    function encode_object($object) {
        try {
                $utf8Object = $this->utf8ize($object);
                $data = json_encode($utf8Object, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
                if (false === $data || !$data || '' === $data) {
                    $this->logger->error("Unable to serialize value. Error: " . json_last_error_msg());
                    return '';
                }                
                return $data;
        } catch (\Exception $e) {
                $this->logger->error('unable to serialise object using php json_encode in ' . __METHOD__);
        }
        return '';
    }
}
