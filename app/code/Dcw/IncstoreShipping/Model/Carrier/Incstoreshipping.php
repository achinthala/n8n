<?php

declare(strict_types=1);

namespace Dcw\IncstoreShipping\Model\Carrier;

use Dcw\IncstoreShipping\Checkout\Model\Session as IncStoreShippingSession;
use Dcw\IncstoreShipping\Model\Api;
use Dcw\IncstoreShipping\ViewModel\Data as IncStoreShippingViewModelData;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\Quote;
use Magento\Directory\Model\RegionFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface as LoggerPsr;

class Incstoreshipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'incstoreshipping';

    /**
     * @var ResultFactory
     */
    protected ResultFactory $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected MethodFactory $_rateMethodFactory;

    /**
     * @var Curl
     */
    protected Curl $_curl;

    /**
     * @var Cart
     */
    protected Cart $_cart;

    /**
     * @var IncstoreShippingViewModelData
     */
    protected IncstoreShippingViewModelData $incStoreShippingViewModelData;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $_customerSession;

    /**
     * @var CartItemRepositoryInterface
     */
    protected CartItemRepositoryInterface $_itemRepository;

    /**
     * @var IncStoreShippingSession
     */
    protected IncStoreShippingSession $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $cartRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    protected CartItemInterfaceFactory $cartItemFactory;

    /**
     * @var BackendQuoteSession
     */
    protected BackendQuoteSession $backendQuoteSession;

    /**
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $timezoneInterface;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resource;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlInterface;

    /**
     * @var AddressFactory
     */
    protected AddressFactory $addressFactory;

    /**
     * @var AdapterInterface
     */
    protected AdapterInterface $connection;

    /**
     * @var SessionManagerInterface
     */
    protected SessionManagerInterface $session;

    /**
     * @var Pool
     */
    protected Pool $cacheFrontendPool;

    /**
     * @var StateInterface
     */
    protected StateInterface $cacheState;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var Api
     */
    protected Api $apiClient;

    /**
     * @var Monolog
     */
    protected Monolog $logger;

    /**
     * @var MessageManager
     */
    protected MessageManager $messageManager;

    /**
     * @var RegionFactory
     */
    protected RegionFactory $regionFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerPsr $loggerPsr
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param Cart $cart
     * @param Curl $curl
     * @param CustomerSession $customerSession
     * @param CartItemRepositoryInterface $itemRepository
     * @param IncStoreShippingSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param CartItemInterfaceFactory $cartItemFactory
     * @param BackendQuoteSession $backendQuoteSession
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param TimezoneInterface $timezoneInterface
     * @param ResourceConnection $resource
     * @param IncStoreShippingViewModelData $incStoreShippingViewModelData
     * @param RequestInterface $request
     * @param UrlInterface $urlInterface
     * @param AddressFactory $addressFactory
     * @param SessionManagerInterface $session
     * @param Pool $cacheFrontendPool
     * @param StateInterface $cacheState
     * @param Api $apiClient
     * @param Monolog $logger
     * @param RegionFactory $regionFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface        $scopeConfig,
        ErrorFactory                $rateErrorFactory,
        LoggerPsr                   $loggerPsr,
        ResultFactory               $rateResultFactory,
        MethodFactory               $rateMethodFactory,
        Cart                        $cart,
        Curl                        $curl,
        CustomerSession             $customerSession,
        CartItemRepositoryInterface $itemRepository,
        IncStoreShippingSession     $checkoutSession,
        CartRepositoryInterface     $cartRepository,
        CartItemInterfaceFactory    $cartItemFactory,
        BackendQuoteSession         $backendQuoteSession,
        CacheInterface                $cache,
        SerializerInterface           $serializer,
        TimezoneInterface             $timezoneInterface,
        ResourceConnection            $resource,
        IncstoreShippingViewModelData $incStoreShippingViewModelData,
        RequestInterface              $request,
        UrlInterface                  $urlInterface,
        AddressFactory                $addressFactory,
        SessionManagerInterface       $session,
        Pool                          $cacheFrontendPool,
        StateInterface                $cacheState,
        Api                           $apiClient,
        Monolog                       $logger,
        MessageManager                $messageManager,
        RegionFactory                 $regionFactory,
        array                         $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_cart = $cart;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_customerSession = $customerSession;
        $this->_itemRepository = $itemRepository;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->cartItemFactory = $cartItemFactory;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->timezoneInterface = $timezoneInterface;
        $this->resource = $resource;
        $this->incStoreShippingViewModelData = $incStoreShippingViewModelData;
        $this->request = $request;
        $this->urlInterface = $urlInterface;
        $this->addressFactory = $addressFactory;
        $this->session = $session;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->cacheState = $cacheState;
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->regionFactory = $regionFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $loggerPsr, $data);
    }

    /**
     * Get shipping data from the checkout session
     * @return array|null
     */
    private function getShippingDataFromCheckoutSession() : ?array
    {
        return $this->checkoutSession->getShippingApiDataRaw();
    }

    /**
     * Get shipping custom data from the checkout session
     * @return array|null
     */
    private function getShippingCustomDataFromCheckoutSession() : ?array
    {
        return $this->checkoutSession->getShippingCustomDataRaw();
    }

    /**
     * Set shipping data to checkout session.
     * @param array $shippingData
     * @param bool $clear
     * @return void
     */
    private function setShippingDataFromCheckoutSession(array $shippingData, bool $clear = false) : void
    {
        // Clear any data before set
        if ($clear) {
            $this->checkoutSession->setShippingApiDataRaw(null);
            return;
        }
        $this->checkoutSession->setShippingApiDataRaw($shippingData);
        $this->checkoutSession->setShippingCustomDataRaw($shippingData);
    }

    /**
     * Check changes from the address if it has, and if it should call API or not.
     * @param RateRequest $request
     * @return bool
     */
    private function isShippingDataAddressChanged(RateRequest $request): bool
    {
        $fieldsToCheck = [
            'dest_postcode',
            'dest_street',
            'dest_city',
        ];

        $shippingData = $this->getShippingCustomDataFromCheckoutSession();
        if (!$shippingData) {
            return true;
        }

        $isCartPage = $this->incStoreShippingViewModelData->isCartPage();

        foreach ($fieldsToCheck as $field) {
            if ($request->getData($field) === null) {
                continue;
            }

            $requestValue = trim((string) $request->getData($field));
            $storedValue = isset($shippingData[$field]) ? trim((string) $shippingData[$field]) : '';

            if ($isCartPage && $field === 'dest_postcode') {
                return $storedValue !== $requestValue;
            }

            if ($storedValue !== '' && strcasecmp($storedValue, $requestValue) !== 0) {
                $this->logger->info(sprintf(
                    'IncStoreShipping-API-Mismatch-Address: %s Expected: %s, Got: %s',
                    $field,
                    $storedValue,
                    $requestValue
                ));
                return true;
            }
        }

        if (!$isCartPage) {
            $requestRegionCode = $this->getRegionCodeFromRequest($request);
            $storedRegionCode = isset($shippingData['dest_region_code'])
                ? strtoupper(trim((string) $shippingData['dest_region_code']))
                : '';

            if ($requestRegionCode !== '' && $storedRegionCode !== ''
                && strcasecmp($requestRegionCode, $storedRegionCode) !== 0
            ) {
                $this->logger->info(sprintf(
                    'IncStoreShipping-API-Mismatch-Address: dest_region_code Expected: %s, Got: %s',
                    $storedRegionCode,
                    $requestRegionCode
                ));
                return true;
            }
        }

        return false;
    }

    /**
     * Check changes from the quote itemms, if it has changes call API
     * @return bool
     */
    private function isQuoteItemChanged() : bool
    {
        $shippingData = $this->getShippingCustomDataFromCheckoutSession();
        if (!$shippingData) {
            return true;
        }
        $quoteItems = $this->getQuoteItems();
        if (isset($shippingData['lineItems']) && !empty($shippingData['lineItems']) && count($shippingData['lineItems']) !=  count($quoteItems)) {
            $this->logger->info('IncStoreShipping-API-Mismatch-Item: session item count not match');
            return true;
        }

        $fieldToCheck = ['sku', 'qty'];
        foreach ($quoteItems as $item) {
            if (!isset($shippingData['lineItems'][$item->getId()]) || empty($shippingData['lineItems'][$item->getId()])) {
                $this->logger->info('IncStoreShipping-API-Mismatch-Item-Count: Condition Failed: lineItems for item ID ' . $item->getId() . ' is empty');
                return true;
            }
            foreach ($fieldToCheck as $field) {
                if (isset($shippingData['lineItems'][$item->getId()][$field]) && !empty($shippingData['lineItems'][$item->getId()][$field])) {
                    if ($shippingData['lineItems'][$item->getId()][$field] != $item->getData($field)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param string|null $customMessage
     * @return Error
     */
    private function getErrorMessage(?string $customMessage = null) : Error
    {
        $customMessage = null;
        $error = $this->_rateErrorFactory->create();
        $error->setCarrier($this->getCarrierCode());
        $error->setCarrierTitle($this->getConfigData('title'));
        if (!(null)) {
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
        } else {
            $error->setErrorMessage($customMessage);
        }
        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active') || empty($request->getData('dest_postcode'))) {
            return false;
        }

        // Return false if it's an add to cart operation
        if ($this->isAddToCartOperation()) {
            return false;
        }

        $isCartPage = $this->incStoreShippingViewModelData->isCartPage();
        $isCheckoutPage = $this->incStoreShippingViewModelData->isCheckoutPage();
		$isPayPalUrl = $this->incStoreShippingViewModelData->isPayPalUrl();
		$isSplitpaymentUrl = $this->incStoreShippingViewModelData->isSplitpaymentUrl();
		$pageUrl = $this->incStoreShippingViewModelData->getPageUrl();
        $isAmazonPayUrl = $this->incStoreShippingViewModelData->isAmazonPayUrl();
        $isAmastyQuote = $this->incStoreShippingViewModelData->isAmastyQuote();
        $isOrderCreateFromAdmin = $this->incStoreShippingViewModelData->isOrderCreateFromAdmin();

        if ($isCartPage || $isAmazonPayUrl || $isSplitpaymentUrl || $isCheckoutPage || $isPayPalUrl) {
            //do nothing flow continue
        } else {
		    $this->logger->info('pageUrl: ' . $pageUrl);
            return false; //dont call collectRates function

        }

        $this->logger->info('IncStoreShipping-API-Start: ShippingPrice API Call for Quote ID: ' . $request->getQuoteId());
        $shippingPrice = 0;
        // Find shipping data from checkout session
        $shippingData = $this->getShippingDataFromCheckoutSession();
        if ($shippingData === null) {
            // Api call needs it
            $this->logger->info('IncStoreShipping-API-Start: ShippingPrice API Call for Quote ID: ' . $request->getQuoteId() . ' - No shipping data in session, calling API');
            $regionCode = $this->getRegionCodeFromRequest($request);
            $apiResponse = $this->getApiPrice(
                $request->getData('dest_postcode'),
                $request->getData('dest_street'),
                $request->getData('dest_city'),
                $regionCode
            );
            if ($apiResponse['isSuccessful']) {
                $shippingData = $this->getShippingDataFromCheckoutSession();
                if (isset($shippingData['charge'])) {
                    $shippingPrice = $shippingData['charge'];
                } else {
                    $this->logger->error('IncStoreShipping-API-Error: No charge key in fresh API response, shipping price will be zero');
                    $shippingPrice = 0;
                }
            } else {
                if ($isCheckoutPage) {
                    $this->logger->error('IncStoreShipping-API-Error: API call failed during address/item change, throwing exception 2');
                    // If API fails during address/item change, throw exception
                    throw new LocalizedException(__("Your order requires our flooring experts to make sure you get the best shipping rates please chat or call at 866-416-6388 for assistance."));
                }
            }
        } else {
            $this->logger->info('IncStoreShipping-API-Start: ShippingPrice API Call for Quote ID: ' . $request->getQuoteId() . ' - Shipping data in session, using cached shipping data');
            // Getting data from checkout session
            if (isset($shippingData['charge'])) {
                $shippingPrice = $shippingData['charge'];
            } else {
                $this->logger->error('IncStoreShipping-API-Error: No charge key in session data, shipping price will be zero');
                $shippingPrice = 0;
            }
        }

        // Checking mismatch could be difference in address or items.
        if ($shippingData !== null) {
            $isAddressChanged = $this->isShippingDataAddressChanged($request);
            $isQuoteItemChanged = false;
            // Check the quote changes only if the address was not changed.
            if (!$isAddressChanged) {
                $isQuoteItemChanged = $this->isQuoteItemChanged();
            }
            // If there are difference on address or items Api call need it.
            if ($isAddressChanged || $isQuoteItemChanged) {
                $this->logger->info('IncStoreShipping-API-Mismatch-Address-Or-Item: Address or item changed, calling API');
                $regionCode = $this->getRegionCodeFromRequest($request);
                $apiResponse = $this->getApiPrice(
                    $request->getData('dest_postcode'),
                    $request->getData('dest_street'),
                    $request->getData('dest_city'),
                    $regionCode
                );
                if ($apiResponse['isSuccessful']) {
                    $shippingData = $this->getShippingDataFromCheckoutSession();
                    if (isset($shippingData['charge'])) {
                        $shippingPrice = $shippingData['charge'];
                    } else {
                        $this->logger->error('IncStoreShipping-API-Error: No charge key in address change API response, shipping price will be zero');
                        $shippingPrice = 0;
                    }
                } else {
                    $this->logger->error('IncStoreShipping-API-Error: API call failed during address/item change');
                    if ($isCheckoutPage) {
                        $this->logger->error('IncStoreShipping-API-Error: API call failed during address/item change, throwing exception 1');
                        // If API fails during address/item change, throw exception
                        throw new LocalizedException(__("Your order requires our flooring experts to make sure you get the best shipping rates please chat or call at 866-416-6388 for assistance."));
                    }
                }
            } else {
                $this->logger->info('IncStoreShipping-API: No address/item changes, using cached shipping data');
                // No address or item changes, use existing cached data
                if (isset($shippingData['charge'])) {
                    $this->logger->info('IncStoreShipping-API: No address/item changes, using cached shipping data: ' . $shippingData['charge']);
                    $shippingPrice = $shippingData['charge'];
                } else {
                    $this->logger->error('IncStoreShipping-API-Error: No charge key in cached data, shipping price will be zero');
                    $shippingPrice = 0;
                }
            }
        }

        // The Customer is going through the checkout set values for checkout restriction.
        $isPaymentInfoCall = $this->paymentInfoCall();
        if ($isPaymentInfoCall) {
            $quote = $this->getCurrentQuote();
            $shippingAddress = $quote->getShippingAddress();
            $quoteId = $quote->getId();
            $this->checkoutSession->setData('is_from_checkout_restriction', 1);
            $this->savePriceToQuote($quoteId, $shippingPrice, $shippingAddress);
            $this->logger->info('IncStoreShipping-API: Proceeding from checkout.');
        } else {
            $this->checkoutSession->setData('is_from_checkout_restriction', 0);
            $this->checkoutSession->setData('calculate_shipping', 0);
        }

        $result = $this->_rateResultFactory->create();

        if ($shippingData !== null) {
            $method = $this->_rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));
            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);
            
            // CRITICAL: Log if shipping price is zero
            if ($shippingPrice == 0) {
                $this->logger->error('IncStoreShipping-CRITICAL: ZERO SHIPPING PRICE for Quote ID: ' . $request->getQuoteId() . ' - Address: ' . $request->getData('dest_postcode') . ', ' . $request->getData('dest_city'));
            } else {
                $this->logger->info('IncStoreShipping-SUCCESS: Shipping price set to ' . $shippingPrice . ' for Quote ID: ' . $request->getQuoteId());
            }
            $result->append($method);
			$quoteId = $this->checkoutSession->getQuoteId();
            $this->logger->info("IncStoreShipping-API-CollectRates Quote Id:".$quoteId." shippingPrice set to Carrier: " . $shippingPrice);

            // Force refresh of checkout config to update frontend with new shipping price
            try {
                // Ensure shipping amounts are saved to quote address for persistence
                $this->saveShippingAmountsToQuote($shippingPrice);
                $this->logger->info("IncStoreShipping-API-CollectRates Quote Id:".$quoteId." saveShippingAmountsToQuote: " . $shippingPrice);
            } catch (\Exception $e) {
                $this->logger->info("IncStoreShipping-API-CollectRates Quote Id:".$quoteId." saveShippingAmountsToQuote error: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * getAllowedMethods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * Build shipping meta data.
     * @param string $transactionId
     * @param array $shippingApiValue
     * @return array
     */
    private function buildShippingMetaData(string $transactionId, array $shippingApiValue) : array
    {
        $shipping_metadata = [];
        $shipping_metadata['transactionId']= $transactionId;
        $shipping_metadata['isValid']= $shippingApiValue['isValid'];
        $shipping_metadata['message']= $shippingApiValue['message'];
        $shipping_metadata['lineItemId']= $shippingApiValue['lineItemId'];
        $shipping_metadata['cost']= $shippingApiValue['cost'];
        $shipping_metadata['charge']= $shippingApiValue['charge'];
        $shipping_metadata['name']= $shippingApiValue['fulfillmentLocation']['name'];
        $shipping_metadata['street']= $shippingApiValue['fulfillmentLocation']['address']['street'];
        $shipping_metadata['street2']= $shippingApiValue['fulfillmentLocation']['address']['street2'];
        $shipping_metadata['city']= $shippingApiValue['fulfillmentLocation']['address']['city'];
        $shipping_metadata['state']= $shippingApiValue['fulfillmentLocation']['address']['state'];
        $shipping_metadata['zipCode']= $shippingApiValue['fulfillmentLocation']['address']['zipCode'];
        $shipping_metadata['country']= $shippingApiValue['fulfillmentLocation']['address']['country'];
        $shipping_metadata['carrierName']= $shippingApiValue['carrierName'];
        return $shipping_metadata;
    }

    /**
     * Get transaction ID
     * @param Quote $quote
     * @return string
     * @throws \Exception
     */
    private function getTransactionOrderId(Quote $quote) : string
    {
        if (!empty($quote->getReservedOrderId())) {
            $transactionId = $quote->getReservedOrderId();
            $this->logger->info('IncStoreShipping-API-TransactionId ( ReservedOrderId ) EXIST = ' . $transactionId);
        } else {
            $quote->reserveOrderId();
            $transactionId = $quote->getReservedOrderId();
            $quote->setReservedOrderId($transactionId);
            $quote->save();
            $this->logger->info('IncStoreShipping-API-TransactionId ( ReservedOrderId ) CREATE NEW & SAVE = ' . $transactionId);
        }
        return $transactionId;
    }

    private function setParamsBeforeSentToApi($transactionId, $street, $city, $state, $postcode)
    {
        $params = [];
        $params['transactionId'] = $transactionId;
        $params['shipToAddress'] = [
            'street' => $street,
            'city'=> $city,
            'state'=> $state,
            'zipCode' => $postcode
        ];
        $params['DeliveryOptions'] = [
            'DeliveryNotification' => true,
            'IsLiftGateRequired' => true,
            'IsResidential' => true
        ];
        $params['lineItems'] = [];
        $items = $this->getQuoteItems();
        foreach ($items as $item) {
            if ($item->getWeight()) {
                $unitWeight = $item->getWeight();
                $unitWeightCal = $this->incStoreShippingViewModelData->calculateUnitWeight($item);

                if ($unitWeightCal) {
                    $unitWeight = $unitWeightCal;
                }

                $params['lineItems'][] = ['Id' => $item->getId(), 'Sku' => $item->getSku(), 'UnitWeight' => $unitWeight, 'Quantity' => $item->getQty()];
            }
        }
        return $params;
    }

    /**
     * @param int $itemId
     * @param Quote $quote
     * @param string $shippingMetaDataJson
     * @param float $shippingCharge
     * @return bool
     * @throws \Exception
     */
    private function updateShippingMetaDataOnQuoteItemTable(int $itemId, Quote $quote, string $shippingMetaDataJson, float $shippingCharge) : bool
    {
        $connection = $this->resource->getConnection();
        $quoteItemTable = $connection->getTableName('quote_item');
        $select = $connection->select()->from($quoteItemTable, 'item_id')->where('parent_item_id = ?', $itemId);
        $quoteItemChild = $connection->fetchOne($select);
        if (!empty($quoteItemChild)) {
            $quoteItem = $quote->getItemById($quoteItemChild);
            $quoteItem->setData('shipping_metadata', $shippingMetaDataJson);
            $quoteItem->setData('incstore_item_shipping', $shippingCharge);
            $quoteItem->save();
            return true;
        }
        return false;
    }

    /**
     * @param string|null $postcode
     * @param string|null $street
     * @param string|null $city
     * @param string|null $state
     * @return false|false[]
     * @throws \Exception
     */
    public function getApiPrice(?string $postcode, ?string $street, ?string $city, ?string $state)
    {
        $shippingApiData = [];
        $quote = $this->_cart->getQuote();
        $transactionId = $this->getTransactionOrderId($quote);
        $params = $this->setParamsBeforeSentToApi($transactionId, $street, $city, $state, $postcode);

        $apiResponse = $this->apiClient->apiCaller($params);

        if ($apiResponse['isSuccessful']) {
            $subTotalCharge = 0;
            foreach ($apiResponse['data']['lineItemShipments'] as $key => $value) {
                //check if any item shipment is not valid
                if (isset($value['isValid']) && !$value['isValid']) {
                    //Todo: what we should do if an item it not valid
                    $this->logger->info('IncStoreShipping-API-Response invalid item id: ' . $value['lineItemId']);
                    return false;
                }
                // Associate charge and shipFrom with the corresponding line item
                $itemId = $value['lineItemId'];
                if (isset($itemId)) {
                    $quoteItemObj = $quote->getItemById($value['lineItemId']);
                    $shippingApiData['dest_street'] = $street;
                    $shippingApiData['dest_city'] = $city;
                    $shippingApiData['dest_postcode'] = $postcode;
                    $shippingApiData['dest_region_code'] = $state;
                    $shippingApiData['lineItems'][$itemId]['charge'] = $value['charge'];
                    $shippingApiData['lineItems'][$itemId]['shipFrom'] = $value['fulfillmentLocation']['address'];
                    $shippingApiData['lineItems'][$itemId]['weight'] = $quoteItemObj->getWeight();
                    $shippingApiData['lineItems'][$itemId]['qty'] = $quoteItemObj->getQty();
                    $shippingApiData['lineItems'][$itemId]['sku'] = $quoteItemObj->getSku();
                    $shippingMetadata = $this->buildShippingMetaData($transactionId, $value);
                    $shippingMetadataJson = json_encode($shippingMetadata);
                    $quoteItemObj->setData('shipping_metadata', $shippingMetadataJson);
                    $quoteItemObj->setData('incstore_item_shipping', $value['charge']);
                    $quoteItemObj->save();
                    $this->updateShippingMetaDataOnQuoteItemTable((int)$itemId, $quote, $shippingMetadataJson, $value['charge']);
                }
                $subTotalCharge += $value['charge'];
            }
            $shippingApiData['charge'] = $subTotalCharge;
            $apiResponse['charge'] = $subTotalCharge;
            $shippingApiData['timestamp'] = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $this->setShippingDataFromCheckoutSession($shippingApiData);
            
            // Store the full API response for observers
            $this->checkoutSession->setShippingApiDataRaw($apiResponse);
            $this->logger->info('IncStoreShipping-API-shippingApiData ==> ' . print_r($shippingApiData, true));
            $this->checkoutSession->setApiFailResponseCheck(0);
            $this->checkoutSession->setApiResponseAmount($subTotalCharge);
        } else {
            $this->logger->error('IncStoreShipping-API-Response: isSuccessful false');
            // Api response fails, clear all data saved on session.
            $this->setShippingDataFromCheckoutSession([], true);
            $this->checkoutSession->setShippingApiDataRaw(null);
            $this->checkoutSession->setApiFailResponseCheck(1);
            $this->checkoutSession->setApiResponseAmount(0);
        }
        return $apiResponse;
    }

    /**
     * Resolve region code from rate request using dest_region_id or dest_region_code.
     */
    private function getRegionCodeFromRequest(RateRequest $request): string
    {
        $regionId = (int) $request->getData('dest_region_id');
        if ($regionId > 0) {
            $region = $this->regionFactory->create()->load($regionId);
            if ($region->getId()) {
                return strtoupper((string) $region->getCode());
            }
        }

        return strtoupper(trim((string) $request->getData('dest_region_code')));
    }

    /**
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getQuoteItems(): array
    {
        $items = [];
        if ($this->_customerSession->isLoggedIn()) {
            // Customer is logged in
            $quoteId = $this->checkoutSession->getQuoteId();
        } else {
            // Customer is a guest
            $quoteId = $this->checkoutSession->getQuoteId();
            if (!$quoteId) {
                // Is admin
                $adminQuote = $this->backendQuoteSession->getQuote();
                $quoteId = $adminQuote->getId();
            }
        }
        try {
            $items = $this->cartRepository->get($quoteId)->getAllVisibleItems();
        } catch (\Exception $exception) {
            $this->logger->error("IncStoreShipping-API-getQuoteItems: " . $exception->getMessage());
        }
        return $items;
    }

    /**
     * @return bool
     */
    protected function paymentInfoCall() : bool
    {
        $currentUrl = $this->urlInterface->getCurrentUrl();
        $paymentInfoUrl = '/payment-information';
        return strpos($currentUrl, $paymentInfoUrl) !== false;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|Quote
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentQuote()
    {
        // Get the current quote
        return $this->checkoutSession->getQuote();
    }

    public function savePriceToQuote($quoteId, $shippingAmount, $shippingAddress)
    {
        $addressCollection = $this->addressFactory->create()->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('address_type', 'shipping')
            ->setPageSize(1);

        if ($addressCollection->getSize() > 0) {
            $addressData = $addressCollection->getFirstItem();
        }

        if ($shippingAddress && $shippingAmount !== false) {
            $shippingAddress->setShippingChargeApi($shippingAmount);
            $shippingAddress->save();
        }
    }

    /**
     * Save shipping amounts to quote address.
     * @param float $shippingAmount
     */
    private function saveShippingAmountsToQuote(float $shippingAmount)
    {
        $quote = $this->getCurrentQuote();
        if ($quote) {
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress) {
                $quote->setShippingMethod($this->_code . '_' . $this->_code);
                $shippingAddress->setShippingAmount($shippingAmount);
                $shippingAddress->setBaseShippingAmount($shippingAmount);
                $shippingAddress->setShippingInclTax($shippingAmount);
                $shippingAddress->setBaseShippingInclTax($shippingAmount);
                $shippingAddress->setShippingChargeApi($shippingAmount);
                $shippingAddress->save();
                $quote->setShippingAmount($shippingAmount);
                $quote->setBaseShippingAmount($shippingAmount);
                $quote->setShippingInclTax($shippingAmount);
                $quote->setBaseShippingInclTax($shippingAmount);
                $quote->setTotalsCollectedFlag(false);
            }
        }
    }

    /**
     * Check if current request is an add to cart operation
     * @return bool
     */
    protected function isAddToCartOperation() : bool
    {
        $currentUrl = $this->urlInterface->getCurrentUrl();
        $addToCartUrls = [
            '/checkout/cart/add',
            '/amastycart/cart/add',
            '/amasty_cart/cart/add',
            '/flooring/index/cartpost',
            '/checkout/cart',
            '/checkout/cart/updateItemOptions',
            '/amasty_quote/quote_edit/',
            '/amasty_quote/quote_create/'
        ];
        
        foreach ($addToCartUrls as $url) {
            if (strpos($currentUrl, $url) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
