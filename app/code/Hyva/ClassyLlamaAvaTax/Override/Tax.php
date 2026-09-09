<?php
/**
 * Avalara_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2018 Avalara, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Hyva\ClassyLlamaAvaTax\Override;

use Avalara\BatchAdjustTransactionModel;
use Avalara\CreateTransactionBatchRequestModel;
use Avalara\TransactionBatchItemModel;
use Avalara\TransactionBuilder;
use Avalara\AvaTax\Api\RestTaxInterface;
use Avalara\AvaTax\Framework\Interaction\Rest;
use Avalara\AvaTax\Framework\Interaction\Rest\Tax\Result;
use Avalara\AvaTax\Helper\Config;
use Avalara\AvaTax\Model\Factory\TransactionBuilderFactory;
use Avalara\AvaTax\Exception\AvataxConnectionException;
use Avalara\AvaTax\Framework\Interaction\Rest\Tax\ResultFactory as TaxResultFactory;
use Avalara\AvaTax\Helper\Rest\Config as RestConfig;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Avalara\AvaTax\Framework\Interaction\Rest\ClientPool;
use Avalara\AvaTax\Helper\CustomsConfig;
use Avalara\AvaTax\Helper\ApiLog;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\OrderFactory;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShippingViewModelData;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;

class Tax extends \Avalara\AvaTax\Framework\Interaction\Rest\Tax
{
    const LINE_PARAM_NAME_UNIT_NAME = 'AvaTax.LandedCost.UnitName';
    const LINE_PARAM_NAME_UNIT_AMT = 'AvaTax.LandedCost.UnitAmount';
    const LINE_PARAM_NAME_PREF_PROGRAM = 'AvaTax.LandedCost.PreferenceProgram';
    const TRANSACTION_PARAM_NAME_SHIPPING_MODE = 'AvaTax.LandedCost.ShippingMode';

    /**
     * @var TransactionBuilderFactory
     */
    protected $transactionBuilderFactory;

    /**
     * @var TaxResultFactory
     */
    protected $taxResultFactory;

    /**
     * @var RestConfig
     */
    protected $restConfig;

    /**
     * @var CustomsConfig
     */
    protected $customsConfigHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ApiLog
     */
    protected $apiLog;

    /**
     * @var Curl
     */
    
    protected $_curl;

    /**
     * @var Cart
     */

    protected $_cart;

    /**
     * @var ScopeConfigInterface
     */

    protected $scopeConfig;

    protected $_logger;

    protected $checkoutSession;

    protected $orderFactory;

    /**
     * @var IncstoreShippingViewModelData
     */
    protected $incstoreShippingViewModelData;

    /**
     * @var ResourceConnection
     */
    protected $resource;
    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var InvoiceCollectionFactory
     */
    protected $invoiceCollectionFactory;


    /**
     * @param LoggerInterface $logger
     * @param DataObjectFactory $dataObjectFactory
     * @param ClientPool $clientPool
     * @param TransactionBuilderFactory $transactionBuilderFactory
     * @param TaxResultFactory $taxResultFactory
     * @param RestConfig $restConfig
     * @param CustomsConfig $customsConfigHelper
     * @param Config $config
     * @param ApiLog $apiLog
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        DataObjectFactory $dataObjectFactory,
        ClientPool $clientPool,
        TransactionBuilderFactory $transactionBuilderFactory,
        TaxResultFactory $taxResultFactory,
        RestConfig $restConfig,
        CustomsConfig $customsConfigHelper,
        Config $config,
        ApiLog $apiLog,
        Curl $curl,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderFactory $orderFactory,
        IncstoreShippingViewModelData $incstoreShippingViewModelData,
        ResourceConnection $resource,
        InvoiceRepositoryInterface $invoiceRepository,
        InvoiceCollectionFactory $invoiceCollectionFactory
    ) {
        parent::__construct($logger, $dataObjectFactory, $clientPool, $transactionBuilderFactory, $taxResultFactory, $restConfig, $customsConfigHelper, $config, $apiLog);
        $this->transactionBuilderFactory = $transactionBuilderFactory;
        $this->taxResultFactory = $taxResultFactory;
        $this->restConfig = $restConfig;
        $this->customsConfigHelper = $customsConfigHelper;
        $this->config = $config;
        $this->apiLog = $apiLog;
        $this->_curl = $curl;
        $this->_cart = $cart;
        $this->scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->incstoreShippingViewModelData = $incstoreShippingViewModelData;
        $this->resource = $resource;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
    }

    /**
     * REST call to post tax transaction
     *
     * @param DataObject $request
     * @param null|bool $isProduction
     * @param null|string|int $scopeId
     * @param string $scopeType
     * @param array $params
     *
     * @return Result
     * @throws LocalizedException
     * @throws AvataxConnectionException
     * @throws Exception
     */
    public function getTax( $request, $isProduction = null, $scopeId = null, $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $params = [])
    {
        $exeEndTime = $apiStartTime = $apiEndTime = 0;
        $exeStartTime = microtime(true);
        $sendLog = true;
        if ($request->hasCode()) {
            $logContext = ['extra' => ['DocCode' => $request->getCode()]];
        }
       
        $client = $this->getClient( $isProduction, $scopeId, $scopeType);
        $client->withCatchExceptions(false);

        /** @var \Avalara\TransactionBuilder $transactionBuilder */
        $transactionBuilder = $this->transactionBuilderFactory->create([
            'client' => $client,
            'companyCode' => $request->getCompanyCode(),
            'type' => $request->getType(),
            'customerCode' => $request->getCustomerCode(),
            'dateTime' => $request->getDate(),
        ]);
        $this->customsConfigHelper->initNextIncrementForWithParameter();

        $this->setTransactionDetails($transactionBuilder, $request);
        $this->setLineDetails($transactionBuilder, $request);
        $logContext['extra']['LineCount'] = $transactionBuilder->getCurrentLineNumber() - 1;
        //$this->setAddressDetails($transactionBuilder, $request);

        $resultObj = null;
        // Fallback to the old request data in case the `createAdjustmentRequest` method changes in the future
        $requestData = $request->getData();
        // Grab the request, which is the model. This is private, but this method gives us access
        $createAdjustmentRequest = $transactionBuilder->createAdjustmentRequest(null, null);

        if(isset($createAdjustmentRequest['newTransaction'])) {
            $requestData = $createAdjustmentRequest['newTransaction'];
        }

        try {
            $apiStartTime = microtime(true);
            $resultObj = $transactionBuilder->create();
            $apiEndTime = microtime(true);
        }
        catch (\GuzzleHttp\Exception\RequestException $clientException) {
            $sendLog = false;
            $debugLogContext = [];
            $debugLogContext['message'] = $clientException->getMessage();
            $debugLogContext['source'] = 'tax';
            $debugLogContext['operation'] = 'Framework_Interaction_Rest_Tax';
            $debugLogContext['function_name'] = 'getTax';
            $this->apiLog->debugLog($debugLogContext, $scopeId, $scopeType);
            $this->handleException($clientException, $request);
        } catch (\Throwable $exception) {
            $debugLogContext = [];
            $debugLogContext['message'] = $exception->getMessage();
            $debugLogContext['source'] = 'customer';
            $debugLogContext['operation'] = 'Framework_Interaction_Rest_Customer';
            $debugLogContext['function_name'] = 'getTax';
            $this->apiLog->debugLog($debugLogContext, $scopeId, $scopeType);
            throw $exception;
        }

        $resultGeneric = $this->formatResult($resultObj);
        /** @var \Avalara\AvaTax\Framework\Interaction\Rest\Tax\Result $result */
        $result = $this->taxResultFactory->create(['data' => $resultGeneric->getData()]);
        // TODO: Could be done better by undoing the recursive `formatResult` call, but that seems wasteful
        $result->setData('raw_result', $resultObj);
        $result->setData('raw_request', $requestData);

        /**
         * We store the request on the result so we can map request items to response items
         */
        $result->setRequest($request);

        if ($sendLog) {
            $exeEndTime = microtime(true);
            $prefix = '';
            $eventBlock = '';
            $docType = '';
            switch ($request->getType()) {
                case \Avalara\DocumentType::C_RETURNINVOICE :
                    $eventBlock = "CreditMemoPostCalculateTax";
                    $docType = "REFUND";
                    $prefix = 'CM';
                    $logContext['extra']['DocCode'] = $prefix . $request->getPurchaseOrderNo() ;
                    break;
                case \Avalara\DocumentType::C_SALESINVOICE :
                    $eventBlock = "InvoicePostCalculateTax";
                    $docType = "INVOICE";
                    $prefix = 'INV';
                    $logContext['extra']['DocCode'] = $prefix . $request->getPurchaseOrderNo() ;
                    break;
                case \Avalara\DocumentType::C_SALESORDER :
                    $eventBlock = "PostCalculateTax";
                    $docType = "SalesOrder";
                    break;
            }        
            if (!empty($docType)) {
                $log['DocCode'] = $request->getPurchaseOrderNo();
                $logContext['extra']['EventBlock'] = $eventBlock;
                $logContext['extra']['DocType'] = $docType;
                $logContext['extra']['ConnectorTime'] = ['start' => $exeStartTime, 'end' => $exeEndTime];
                $logContext['extra']['ConnectorLatency'] = ['start' => $apiStartTime, 'end' => $apiEndTime];
                $logContext['source'] = 'tax';
                $logContext['operation'] = 'calculateTax';
                $logContext['function_name'] = __METHOD__;
                $this->apiLog->makeTransactionRequestLog($logContext, $scopeId, $scopeType);
            }
        }

        return $result;
    }

    /**
     * Set transaction-level fields for request
     *
     * @param TransactionBuilder $transactionBuilder
     * @param DataObject $request
     */
    protected function setTransactionDetails($transactionBuilder, $request)
    {
        if ($request->getCommit()) {
            $transactionBuilder->withCommit();
        }
        if ($request->hasIsSellerImporterOfRecord()) {
            $transactionBuilder->withSellerIsImporterOfRecord($request->getIsSellerImporterOfRecord());
        }

        if ($request->hasCode()) {
            $transactionBuilder->withTransactionCode($request->getCode());
        }
        if ($request->hasBusinessIdentificationNo()) {
            $transactionBuilder->withBusinessIdentificationNo($request->getBusinessIdentificationNo());
        }
        if ($request->hasCurrencyCode()) {
            $transactionBuilder->withCurrencyCode($request->getCurrencyCode());
        }
        if ($request->hasEntityUseCode()) {
            $transactionBuilder->withEntityUseCode($request->getEntityUseCode());
        }
        if ($request->hasDiscount()) {
            $transactionBuilder->withDiscountAmount($request->getDiscount());
        }
        if ($request->hasExchangeRate()) {
            $transactionBuilder->withExchangeRate($request->getExchangeRate(),
                $request->getExchangeRateEffectiveDate());
        }
        if ($request->hasReportingLocationCode()) {
            $transactionBuilder->withReportingLocationCode($request->getReportingLocationCode());
        }
        if ($request->hasPurchaseOrderNo()) {
            $transactionBuilder->withPurchaseOrderNo($request->getPurchaseOrderNo());
        }
        if ($request->hasReferenceCode()) {
            $transactionBuilder->withReferenceCode($request->getReferenceCode());
        }
        if ($request->hasTaxOverride()) {
            $override = $request->getTaxOverride();
            if (is_object($override)) {
                $transactionBuilder->withTaxOverride($override->getType(), $override->getReason(),
                    $override->getTaxAmount(), $override->getTaxDate());
            }
        }
        if ($request->hasTransportParameters()) {
            $transactionBuilder->withParameter($this->customsConfigHelper->getNextIncrementForWithParameter(), ['name'=>$request->getShippingMethod(), 'value'=>$request->getTransportParametersValue()] );
        }
        if ($request->hasShippingParameters()) {
            $transactionBuilder->withParameter($this->customsConfigHelper->getNextIncrementForWithParameter(), ['name'=>$request->getShippingParametersName(), 'value'=>$request->getShippingParametersValue()]);
        }
    }


    private function formatDataForLog($data) //delete
    {
        if (is_object($data)) {
            // Convert object to array for logging
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            // Recursively handle nested arrays and objects
            foreach ($data as $key => $value) {
                $data[$key] = $this->formatDataForLog($value);
            }
        }

        // If not array or object, return the value directly
        return $data;
    }


    /**
     * Set address entries and fields for request
     *
     * @param TransactionBuilder $transactionBuilder
     * @param DataObject $request
     * @throws Exception
     */
    protected function setLineDetails($transactionBuilder, $request)
    {
        // custom code start
        if ($request->hasLines()) {
            $itemsShipFrom = [];
            if ($request->hasAddresses()) {
                foreach ($request->getAddresses() as $type => $address) {
                    if($request->getCommit() != 1 && $type == "ShipTo" && $address->getPostalCode()){
                        if (!empty($this->checkoutSession->getShippingApiData())) {
                            $sessionShipApi = $this->checkoutSession->getShippingApiData();
                            
                            $sessionShipForm = [];
                            if(!empty($sessionShipApi['shipFrom'])) {
                                $sessionShipForm['street'] = $sessionShipApi['shipFrom']['street'];
                                $sessionShipForm['city'] = $sessionShipApi['shipFrom']['city'];
                                $sessionShipForm['state'] = $sessionShipApi['shipFrom']['state'];
                                $sessionShipForm['zipCode'] = $sessionShipApi['shipFrom']['zipCode'];
                                $sessionShipForm['country'] = $sessionShipApi['shipFrom']['country'];
                            }
                        } else {
                            $itemsShipFrom = $this->getItemsShipFrom($address->getPostalCode(),$address->getLine1(),$address->getCity(),$address->getRegion(),$request->getPurchaseOrderNo());   
                        }
                    } else {
                        $invoiceShipFrom = $this->getInvoiceItemsShipFrom($request->getReferenceCode());
                    }
                }
            }

            $firstShipFrom = [];
            if(!empty($itemsShipFrom) && isset($itemsShipFrom['data'])){
				$firstItemAddresData = $itemsShipFrom['data'];
				foreach ($firstItemAddresData['lineItemShipments'] as $key => $value) {
					if(isset($value['fulfillmentLocation'])){
						$firstShipFrom = $value['fulfillmentLocation']['address'];
						
					}
				}
            }
            /// custom code end
            if(!empty($sessionShipForm)){
                $firstShipFrom = $sessionShipForm;
            }
            $lineItemArray=[];
            $j=0;
            foreach ($request->getLines() as $line) {
                $amount = ($line->hasAmount()) ? $line->getAmount() : 0;
                $transactionBuilder->withLine($amount, $line->getQuantity(), $line->getItemCode(), $line->getTaxCode());
                if ($line->getTaxIncluded()) {
                    $transactionBuilder->withLineTaxIncluded();
                }

                if ($line->hasDescription()) {
                    $transactionBuilder->withLineDescription($line->getDescription());
                }
                if ($line->hasDiscounted()) {
                    $transactionBuilder->withItemDiscount($line->getDiscounted());
                }
                if ($line->hasRef1() || $line->hasRef2()) {
                    $transactionBuilder->withLineCustomFields($line->getRef1(), $line->getRef2());
                }

                if ($this->customsConfigHelper->enabled()) {
                    if ($line->hasHsCode() && $line->getHsCode() !== '') {
                        $transactionBuilder->withLineHsCode($line->getHsCode());
                    }
                    if ($line->hasUnitName() && $line->getUnitName() !== '') {
                        $transactionBuilder->withLineParameter(self::LINE_PARAM_NAME_UNIT_NAME, $line->getUnitName());
                    }
                    if ($line->hasUnitAmount() && $line->getUnitAmount() !== '') {
                        $transactionBuilder->withLineParameter(self::LINE_PARAM_NAME_UNIT_AMT, $line->getUnitAmount());
                    }
                    if ($line->hasPreferenceProgram() && $line->getPreferenceProgram() !== '') {
                        $transactionBuilder->withLineParameter(self::LINE_PARAM_NAME_PREF_PROGRAM,
                            $line->getPreferenceProgram());
                    }
                }

                // custom code start
                
                if ($request->hasAddresses()) {
                    
                    foreach ($request->getAddresses() as $type => $address) {
                        if( $type != "PointOfOrderOrigin"){
                            if( $type == "ShipFrom"){
                                
                                $itemIndex = $address->getIndex(); // Assuming $address provides an index for matching
                                
                                $lineItemShipFrom = $this->getShipFromForItem($j,$request->getReferenceCode());
                                $j++;
                                
                                if(!empty($lineItemShipFrom)){
                                    $transactionBuilder->withLineAddress(
                                        $type,
                                        $lineItemShipFrom['street'],
                                        '',
                                        '',
                                        $lineItemShipFrom['city'],
                                        $lineItemShipFrom['state'],
                                        $lineItemShipFrom['zipCode'],
                                        $lineItemShipFrom['country']
                                    );
                                }else if (!empty($invoiceShipFrom)){
                                    $transactionBuilder->withLineAddress(
                                        $type,
                                        $invoiceShipFrom['street'],
                                        '',
                                        '',
                                        $invoiceShipFrom['city'],
                                        $invoiceShipFrom['state'],
                                        $invoiceShipFrom['zipCode'],
                                        $invoiceShipFrom['country']
                                    );
                                } /*else{
                                    $transactionBuilder->withLineAddress(
                                        $type,
                                        $address->getLine1(),
                                        $address->getLine2(),
                                        $address->getLine3(),
                                        $address->getCity(),
                                        $address->getRegion(),
                                        $address->getPostalCode(),
                                        $address->getCountry()
                                    );
                                }*/

                              
                            }else{
                                $transactionBuilder->withLineAddress(
                                    $type,
                                    $address->getLine1(),
                                    $address->getLine2(),
                                    $address->getLine3(),
                                    $address->getCity(),
                                    $address->getRegion(),
                                    $address->getPostalCode(),
                                    $address->getCountry()
                                );
                            }
                        }
                        
                    }
                }
                // custom code end
                
                /**
                 * It's only here that we can set the line number on the request items, when we're sure it will be the same as the line number in the response
                 */
                $line->setNumber($transactionBuilder->getCurrentLineNumber());
            }

            $this->addAmastyExtraFee($transactionBuilder, $request);
        }
    }

    private function getShipFromForItem($index,$incrementId)
    {
        $shipFromData = $this->checkoutSession->getShippingApiData();
        if (isset($shipFromData['lineItems']) && is_array($shipFromData['lineItems'])) {
            $indexN = 0;
            
            foreach($shipFromData['lineItems'] as $item){
                if($index==$indexN){ 
                return  $item['shipFrom'] ?? null;
                }
                $indexN++;
            }
        }else{
            $getShipFromItem = $this->getInvoiceItemsShipFromNew($incrementId,$index);
            
            return $getShipFromItem;

        }
        //return $shipFromData['lineItems'][$index]['shipFrom'] ?? null;
        return null;
    }

    /**
     * add amasty Expedite fees to avatax
     */
    public function addAmastyExtraFee($transactionBuilder, $request)
    {
        if ($request->getCode() && $request->getAddresses() && $request->getAddresses()['ShipTo']) {
            
            $quoteId = $this->checkoutSession->getQuote()->getId();

            $getPurchaseOrderNo = $request->getPurchaseOrderNo();

            if (!empty($getPurchaseOrderNo)) {
                $getQuoteIdByInvoiceIncrementId = $this->getQuoteIdByInvoiceIncrementId($getPurchaseOrderNo);

                if ($getQuoteIdByInvoiceIncrementId) {
                    $quoteId = $getQuoteIdByInvoiceIncrementId;
                }
            }
            
            $amastyQuoteTable = $this->resource->getTableName('amasty_extrafee_quote');
            $connection = $this->resource->getConnection();

            $amastyFeeQuoteLoad =  $connection->select()->from($amastyQuoteTable, 'fee_amount' )->where('quote_id = ?', $quoteId);
            $amastyFeeQuoteResult = $connection->fetchAll($amastyFeeQuoteLoad);
            $totalAmastyExtraFee = 0;

            if (count($amastyFeeQuoteResult) > 0) {
                foreach($amastyFeeQuoteResult as $amastyFee) {
                    $totalAmastyExtraFee+= $amastyFee['fee_amount'];
                }

                if ($totalAmastyExtraFee > 0) {
                    $getShipToAddress = $request->getAddresses()['ShipTo'];

                    $shipToLine1 = $getShipToAddress['line_1'];
                    $shipToLine2 = $getShipToAddress['line_2'];
                    $shipToLine3 = $getShipToAddress['line_3'];
                    $shipToCity = $getShipToAddress['city'];
                    $shipToRegion = $getShipToAddress['region'];
                    $shipToPostalCode = $getShipToAddress['postal_code'];
                    $shipToCountry = $getShipToAddress['country'];

                    $transactionBuilder->withLine($totalAmastyExtraFee, 1, 'Expedite Fees', 'OF030000');
                    $transactionBuilder->withLineAddress(
                        'ShipTo',
                        $shipToLine1,
                        $shipToLine2,
                        $shipToLine3,
                        $shipToCity,
                        $shipToRegion,
                        $shipToPostalCode,
                        $shipToCountry
                    );
                }
            }
        }
    }

    /**
     * get the quote_id from invoice increment id
     */
    public function getQuoteIdByInvoiceIncrementId($incrementId)
    {
        try {
            $invoiceCollection = $this->invoiceCollectionFactory->create()
            ->addAttributeToFilter('increment_id', $incrementId)
            ->setPageSize(1); // Expecting only one result

            $invoice = $invoiceCollection->getFirstItem();

            if (!$invoice->getId()) {
                return false;
            }
        
            try {
                // Load invoice by entity ID using repository
                $loadedInvoice = $this->invoiceRepository->get($invoice->getId());
            } catch (NoSuchEntityException $e) {
                $this->_logger->info("Invoice ID ".$invoice->getId()." does not exist: " . $e->getMessage());
                return false;
            }

            // Load invoice by entity ID using repository
            $loadedInvoice = $this->invoiceRepository->get($invoice->getId());

            // Load the order using the order repository or directly from the invoice
            $order = $loadedInvoice->getOrder();

            return $order->getQuoteId();

        } catch (Exception $e) {
            $this->_logger->info("getQuoteIdByInvoiceIncrementId error===".$e->getMessage());
            // Handle the case where the invoice does not exist
            return false;
        }
    }

    /**
     * Set line item entries and fields for request
     *
     * @param TransactionBuilder $transactionBuilder
     * @param DataObject $request
     */
    protected function setAddressDetails($transactionBuilder, $request)
    {
        if ($request->hasAddresses()) {
            foreach ($request->getAddresses() as $type => $address) {
                $transactionBuilder->withAddress(
                    $type,
                    $address->getLine1(),
                    $address->getLine2(),
                    $address->getLine3(),
                    $address->getCity(),
                    $address->getRegion(),
                    $address->getPostalCode(),
                    $address->getCountry()
                );
            }
        }
    }

    /**
     * @param $requests
     * @param null $isProduction
     * @param null $scopeId
     * @param string $scopeType
     * @return Result
     * @throws AvataxConnectionException
     */
    public function getTaxBatch(
        $requests,
        $isProduction = null,
        $scopeId = null,
        $scopeType = ScopeInterface::SCOPE_STORE
    ): Result {
        $exeEndTime = $apiStartTime = $apiEndTime = 0;
        $exeStartTime = microtime(true);
        $sendLogs = true;
        $client = $this->getClient($isProduction, $scopeId, $scopeType);
        $client->withCatchExceptions(false);
        $transactions = [];
        $logs = [];
        foreach ($requests as $request) {
            $log = [];
            $log['DocType'] = $request->getType();
            $log['DocCode'] = $request->getPurchaseOrderNo();
            $sendLog = true;
            $transactionBuilder = $this->transactionBuilderFactory->create([
                'client'       => $client,
                'companyCode'  => $request->getCompanyCode(),
                'type'         => $request->getType(),
                'customerCode' => $request->getCustomerCode(),
                'dateTime'     => $request->getDate(),
            ]);
            $this->customsConfigHelper->initNextIncrementForWithParameter();
            $this->setTransactionDetails($transactionBuilder, $request);
            try {
                $this->setLineDetails($transactionBuilder, $request);
                $log['LineCount'] = $transactionBuilder->getCurrentLineNumber() - 1;
            } catch (Exception $e) {
                $sendLog = false;
            }
            $this->setAddressDetails($transactionBuilder, $request);
            $createAdjustmentRequest = $transactionBuilder->createAdjustmentRequest(null, null);

            $requestData = $createAdjustmentRequest['newTransaction'];

            $transaction = new TransactionBatchItemModel();
            $transaction->createTransactionModel = $requestData;
            $transactions[] = $transaction;
            if ($sendLog)
                $logs[] = $log;
        }
        $transactionBatchRequestModel = new CreateTransactionBatchRequestModel();
        $transactionBatchRequestModel->name = "Batch" . date("Y-m-d H:i:s");
        $transactionBatchRequestModel->transactions = $transactions;

        $resultObj = null;
        try {
            $apiStartTime = microtime(true);
            $resultObj = $client->createTransactionBatch($this->config->getCompanyId(), $transactionBatchRequestModel);
            $apiEndTime = microtime(true);
        } catch (RequestException $clientException) {
            $sendLogs = false;
            $debugLogContext = [];
            $debugLogContext['message'] = $clientException->getMessage();
            $debugLogContext['source'] = 'tax';
            $debugLogContext['operation'] = 'Framework_Interaction_Rest_Tax';
            $debugLogContext['function_name'] = 'getTaxBatch';
            $this->apiLog->debugLog($debugLogContext, $scopeId, $scopeType);
            $this->handleException($clientException);
        }
        $resultGeneric = $this->formatResult($resultObj);
        $result = $this->taxResultFactory->create(['data' => $resultGeneric->getData()]);
        $result->setData('raw_result', $resultObj);
        $result->setData('raw_request', $transactionBatchRequestModel);

        /**
         * We store the request on the result so we can map request items to response items
         */
        $result->setRequest($transactionBatchRequestModel);

        if ($sendLogs && count($logs) > 0) {
            $exeEndTime = microtime(true);
            foreach ($logs as $log) {
                $prefix = '';
                $eventBlock = '';
                $docType = '';
                switch ($log['DocType']) {
                    case \Avalara\DocumentType::C_RETURNINVOICE :
                        $eventBlock = "BatchCreditMemoPostCalculateTax";
                        $docType = "REFUND";
                        $prefix = 'CM';
                        break;
                    case \Avalara\DocumentType::C_SALESINVOICE :
                        $eventBlock = "BatchInvoicePostCalculateTax";
                        $docType = "INVOICE";  
                        $prefix = 'INV';
                        break;
                }
                if (empty($docType)) continue;
                $logContext = [];
                $logContext['extra']['DocCode'] = $prefix.$log['DocCode'];
                $logContext['extra']['DocType'] = $docType;
                if (isset($log['LineCount']))
                    $logContext['extra']['LineCount'] = $log['LineCount'];
                $logContext['extra']['EventBlock'] = $eventBlock;
                $logContext['extra']['ConnectorTime'] = ['start' => $exeStartTime, 'end' => $exeEndTime];
                $logContext['extra']['ConnectorLatency'] = ['start' => $apiStartTime, 'end' => $apiEndTime];
                $logContext['source'] = 'tax';
                $logContext['operation'] = 'calculateTax';
                $logContext['function_name'] = __METHOD__;
                $this->apiLog->makeTransactionRequestLog($logContext, $scopeId, $scopeType);
            }
        }

        return $result;
    }

    // custom code start
    public function getInvoiceItemsShipFrom($referenceCode)
    {
        if ($referenceCode) {
            $order = $this->orderFactory->create()->loadByIncrementId($referenceCode);
            $items = $order->getAllItems();

            foreach ($items as $item) {
                $data_array = $item->getShippingMetadata();

                if ($data_array) {
                    $firstShipFrom['street'] = "";
                    $firstShipFrom['city'] = "";
                    $firstShipFrom['state'] = "";
                    $firstShipFrom['zipCode'] = "";
                    $firstShipFrom['country'] = "";

                    $shippingMetadataObject = json_decode($data_array ?? '');
                    $firstShipFrom = [];

                    if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'street')) {
                        $firstShipFrom['street'] = $shippingMetadataObject->street;
                    }

                    if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'city')) {
                        $firstShipFrom['city'] = $shippingMetadataObject->city;
                    }

                    if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'state')) {
                        $firstShipFrom['state'] = $shippingMetadataObject->state;
                    }

                    if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'zipCode')) {
                        $firstShipFrom['zipCode'] = $shippingMetadataObject->zipCode;
                    }

                    if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'country')) {
                        $firstShipFrom['country'] = $shippingMetadataObject->country;
                    }
                    return $firstShipFrom;
                }
            }
        }
        
        return '';
    }

    public function getInvoiceItemsShipFromNew($referenceCode,$index)
    {
        if ($referenceCode) {
            $order = $this->orderFactory->create()->loadByIncrementId($referenceCode);
            $items = $order->getAllItems();
            
            $indexN=0;
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    $data_array = $item->getShippingMetadata();
                    
                    if ($data_array) {
                        $firstShipFrom['street'] = "";
                        $firstShipFrom['city'] = "";
                        $firstShipFrom['state'] = "";
                        $firstShipFrom['zipCode'] = "";
                        $firstShipFrom['country'] = "";

                        $shippingMetadataObject = json_decode($data_array ?? '');
                        $firstShipFrom = [];

                        if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'street')) {
                            $firstShipFrom['street'] = $shippingMetadataObject->street;
                        }

                        if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'city')) {
                            $firstShipFrom['city'] = $shippingMetadataObject->city;
                        }

                        if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'state')) {
                            $firstShipFrom['state'] = $shippingMetadataObject->state;
                        }

                        if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'zipCode')) {
                            $firstShipFrom['zipCode'] = $shippingMetadataObject->zipCode;
                        }

                        if (isset($shippingMetadataObject) && property_exists($shippingMetadataObject, 'country')) {
                            $firstShipFrom['country'] = $shippingMetadataObject->country;
                        }
                        if($index==$indexN){
                            
                            return $firstShipFrom;
                        }
                        $indexN++;
                    }
                }
            }
        }
        
        return '';
    }

    public function getItemsShipFrom($postcode, $street, $city, $state,$purchaseOrderNo)
    {
        $params['transactionId']= $purchaseOrderNo;
        $params['shipToAddress']= ['street' => $street, 'city'=> $city, 'state'=> $state, 'zipCode' => $postcode];
		$params['DeliveryOptions']= ['DeliveryNotification' => true, 'IsLiftGateRequired' => true, 'IsResidential' => true];
            $items = $this->_cart->getQuote()->getAllVisibleItems();
            foreach ($items as $item) {
                if($item->getWeight()) {
                    $unitWeight = $item->getWeight();

                    $unitWeightCal = $this->incstoreShippingViewModelData->calculateUnitWeight($item);

                    if ($unitWeightCal) {
                        $unitWeight = $unitWeightCal;
                    }
                    
                    $params['lineItems'][] = ['Id' => $item->getItemId(), 'Sku' => $item->getSku(), 'UnitWeight' => $unitWeight, 'Quantity' => $item->getQty()];
                }
            }
        $apiurl = $this->scopeConfig->getValue('dcw_shipping_api/incstoreshipping_config/api_url');
        $apikey = $this->scopeConfig->getValue('dcw_shipping_api/incstoreshipping_config/api_key');
        return [];
    }


    public function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/FixeShipping.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
    // custom code end
}
