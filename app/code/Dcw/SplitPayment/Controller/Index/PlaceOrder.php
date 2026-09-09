<?php
namespace Dcw\SplitPayment\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Gateway\Command\CommandException;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    const SOLUTION_ID = 'A1000133';
    private const RESULT_CODE_OK = 'OK';
    private const CODE_I00001_SUCCESSFULL = 'I00001';
    protected $code = 'authnetcim';
    protected $endpoint;
    protected $endpointLive = 'https://api2.authorize.net/xml/v1/request.api';
    protected $endpointTest = 'https://apitest.authorize.net/xml/v1/request.api';

    protected $log  = '';
    protected $testMode;
    protected $lastResponse;
    protected $lastRequest;

    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \ParadoxLabs\Authnetcim\Model\Gateway $gateway,
        \ParadoxLabs\TokenBase\Model\Gateway\Xml $xml,
        \Magento\Framework\HTTP\ClientInterfaceFactory $communicatorFactory,
        \Magento\Framework\Module\Dir $moduleDir,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->customerSession   = $customerSession;
        $this->resultPageFactory   = $resultPageFactory;
        $this->registry   = $registry;
        $this->cardFactory   = $cardFactory;
        $this->cardRepository   = $cardRepository;
        $this->helper   = $helper;
        $this->addressHelper   = $addressHelper;
        $this->checkoutSession  = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->managerInterface = $managerInterface;
        $this->cookieManager = $cookieManager;
        $this->cart = $cart;
        $this->quoteManagement = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->_json = $json;
        $this->gateway = $gateway;
        $this->xml = $xml;
        $this->communicatorFactory = $communicatorFactory;
        $this->moduleDir = $moduleDir;
        $this->regionFactory = $regionFactory;
        $this->remoteAddress = $remoteAddress;
        $this->_eventManager = $eventManager;

        parent::__construct($context);
    }

    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/split_payment.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(__METHOD__);

        $split_payment_transaction_table = $this->connection->getTableName("split_payment_transaction");

        $this->cart->getQuote()->reserveOrderId();

        $resultJson = $this->resultJsonFactory->create();

        $cc_payment_arr = $this->getRequest()->getPost();
        //$logger->info('cc_payment_arr ='.print_r($cc_payment_arr, true));

        $authorizeTransArr = [];
        $is_cc_authorize_fail = 0;
      /*========================= CC Card authorization  START  ====================== */
        $i = 1;

        $errors = [];

        foreach ($cc_payment_arr as $cc_payment) {
            if ($cc_payment['cc_payment'] <= 0) {
                continue;
            }

            $params = [];
            $params_transReqst = [];
            $profile_info_arr = [];
            $payment_info_arr = [];
            $lineItemArr = [];
            $billToArr = [];
            $shipToArr = [];
            $taxArr = [];
            $result = [];

            $params_transReqst['transactionType'] = "authOnlyTransaction";
            $params_transReqst['amount'] =  $cc_payment['cc_payment'];

            $anet_customer_profile_id = '';
            $anet_customer_payment_id = '';
            $paradoxLabs_tokenbase_id = '';
            if (!empty($cc_payment['id'])) {
                $card = $this->cardRepository->getByHash($cc_payment['id']);
                $profile_info_arr = [
                'customerProfileId'   => $card->getProfileId(),
                'paymentProfile'    => [
                'paymentProfileId' => $card->getPaymentId()
                ],
                ];
                $params_transReqst['profile'] = $profile_info_arr;
                $anet_customer_profile_id = $card->getProfileId();
                $anet_customer_payment_id = $card->getPaymentId();
                $paradoxLabs_tokenbase_id = $card->getId();
            } else {
                $payment_info_arr = [
                "creditCard" => [
                "cardNumber" => $cc_payment['payment']['cc_number'],
                "expirationDate" => $cc_payment['payment']['cc_exp_year'].'-'.$cc_payment['payment']['cc_exp_month'],
                "cardCode" => $cc_payment['payment']['cc_cid']
                ]
                ];
                $params_transReqst['payment'] = $payment_info_arr;
            }

            $store = $this->helper->getCurrentStore();
            $anet_order_number = $this->cart->getQuote()->getReservedOrderId().'-'.$i++;
            $params_transReqst['order'] = [
            'invoiceNumber' => $anet_order_number,
            'description' => $this->modParam($store->getName().' ('.$store->getBaseUrl().')', ['maxLength' => 255])
            ];

            $items = $this->cart->getQuote()->getAllVisibleItems();
            foreach ($items as $item) {
                $lineItemArr['lineItem'][] = [
                'itemId' => $this->modParam($item->getSku(), ['maxLength' => 31, 'noSymbols' => true]),
                'name' => $this->modParam($item->getName(), ['maxLength' => 31, 'noSymbols' => true]),
                'quantity' => $this->formatAmount($item->getQty()),
                'unitPrice' => $this->formatAmount($item->getPrice())
                ];
            }
            $params_transReqst['lineItems'] = $lineItemArr;

            if ($this->cart->getQuote()->getShippingAddress()->getTaxAmount() > 0) {
                $tax_title = '';
                if (is_array($this->cart->getQuote()->getShippingAddress()->getAppliedTaxes())) {
                    $appliedTaxes = $this->cart->getQuote()->getShippingAddress()->getAppliedTaxes();

                    foreach ($appliedTaxes as $appliedTax) {
                        $tax_title = $appliedTax['rates'][0]['title'];
                    }
                }
                $taxArr = [
                "amount" => $this->cart->getQuote()->getShippingAddress()->getTaxAmount(),
                "name" => '',
                "description" => '' //$tax_title
                ];
                $params_transReqst['tax'] = $taxArr;
            }

            $params_transReqst['shipping'] = [
            "amount" => $this->cart->getQuote()->getShippingAddress()->getShippingAmount(),
            "name" => '',
            "description" => '' // $this->cart->getQuote()->getShippingAddress()->getShippingDescription()
            ];

            if (!empty($this->customerSession->getId())) {
                $params_transReqst['customer'] = [
                'id' => $this->cart->getQuote()->getCustomerId(),
                'email' => $this->cart->getQuote()->getCustomerEmail()
                ];
            } else {
                $params_transReqst['customer'] = [
                'email' => $this->cart->getQuote()->getCustomerEmail()
                ];
            }

            if (empty($cc_payment['id']) && empty($cc_payment['is_gc_card'])) {
                $billToArr["firstName"] = $this->modParam($cc_payment['billing']['firstname'], ['maxLength' => 50, 'noSymbols' => true]);
                $billToArr["lastName"] = $this->modParam($cc_payment['billing']['lastname'], ['maxLength' => 50, 'noSymbols' => true]);
                if (!empty($cc_payment['billing']['company'])) {
                    $billToArr["company"] = $this->modParam($cc_payment['billing']['company'], ['maxLength' => 50, 'noSymbols' => true]);
                }
                $address = implode(" ", $cc_payment['billing']['street']);
                $billToArr["address"] = $this->modParam($address, ['maxLength' => 60, 'noSymbols' => true]);
                $billToArr["city"] = $this->modParam($cc_payment['billing']['city'], ['maxLength' => 40, 'noSymbols' => true]);
                if (!empty($cc_payment['billing']['region_id'])) {
                    $region = $this->regionFactory->create()->load($cc_payment['billing']['region_id']);
                    $billToArr["state"] = $region->getCode();
                } else {
                    $billToArr["state"] = $this->modParam($cc_payment['billing']['region'], ['maxLength' => 40, 'noSymbols' => true]);
                }
                $billToArr["zip"] = $this->modParam($cc_payment['billing']['postcode'], ['maxLength' => 20, 'noSymbols' => true]);
                $billToArr["country"] = $cc_payment['billing']['country_id'];
                $billToArr["phoneNumber"] = $this->modParam($cc_payment['billing']['telephone'], ['maxLength' => 25, 'charMask' => '\d\(\)\-\.']);

                $params_transReqst['billTo'] = $billToArr;
            }

            if (empty($cc_payment['is_gc_card'])) {
                $shipToArr["firstName"] = $this->modParam($this->cart->getQuote()->getShippingAddress()->getFirstname(), ['maxLength' => 50, 'noSymbols' => true]);
                $shipToArr["lastName"] = $this->modParam($this->cart->getQuote()->getShippingAddress()->getLastname(), ['maxLength' => 50, 'noSymbols' => true]);
                if (!empty($this->cart->getQuote()->getShippingAddress()->getCompany())) {
                    $shipToArr["company"] = $this->modParam($this->cart->getQuote()->getShippingAddress()->getCompany(), ['maxLength' => 50, 'noSymbols' => true]);
                }
                $address = implode(" ", $this->cart->getQuote()->getShippingAddress()->getStreet());
                $shipToArr["address"] = $this->modParam($address, ['maxLength' => 60, 'noSymbols' => true]);
                $shipToArr["city"] = $this->modParam(
                                            $this->cart->getQuote()->getShippingAddress()->getCity(),
                                            ['maxLength' => 40,
                                            'noSymbols' => true]
                                        );
                if (!empty($this->cart->getQuote()->getShippingAddress()->getRegionId())) {
                    $region = $this->regionFactory->create()->load($this->cart->getQuote()->getShippingAddress()->getRegionId());
                    $shipToArr["state"] = $region->getCode();
                } else {
                $shipToArr["state"] = $this->modParam(
                                            $this->cart->getQuote()->getShippingAddress()->getRegion(),
                                            ['maxLength' => 40,
                                            'noSymbols' => true]
                                        );
                }
                $shipToArr["zip"] = $this->modParam(
                                            $this->cart->getQuote()->getShippingAddress()->getPostcode(),
                                            ['maxLength' => 20,
                                            'noSymbols' => true]
                                        );
                $shipToArr["country"] = $this->cart->getQuote()->getShippingAddress()->getCountryId();

                $params_transReqst['shipTo'] = $shipToArr;
            }

            $params_transReqst['customerIP'] = $this->remoteAddress->getRemoteAddress();

            $params_transReqst['transactionSettings'] = ['setting' => [
            [
            'settingName' => 'allowPartialAuth',
            'settingValue' => false
            ],
            [
            'settingName' => 'duplicateWindow',
            'settingValue' => 30
            ],
            [
            'settingName' => 'emailCustomer',
            'settingValue' => false
            ]
            ]];

            $params_transReqst['processingOptions'] = [
            'isStoredCredentials' => true,
            ];

          //$params['refId'] = $this->modParam('ref'.time(),['maxLength' => 40]);
            $params['transactionRequest'] = $params_transReqst;

            $logger->info('REQUEST CODE = createTransactionRequest');
            $logger->info('REQUEST ='.print_r($params, true));

            $result = $this->runTransaction('createTransactionRequest', $params);

            $logger->info('----');
            $logger->info('result ='.print_r($result, true));
            $logger->info('------------------------------------------------------------------------------------');

            if (!empty($result['messages']['resultCode']) &&
                mb_strtoupper($result['messages']['resultCode']) === self::RESULT_CODE_OK &&
                mb_strtoupper($result['messages']['message']['code']) === self::CODE_I00001_SUCCESSFULL) {
                $authorizeTransArr[] = [
                'trans_id' => $this->helper->getArrayValue($result, 'transactionResponse/transId'),
                'anet_order_number' => $anet_order_number,
                'amount' => $cc_payment['cc_payment']
                ];

              /* ----------------------- add into split_payment_transaction table  START -------------------- */
                $additional_information = [
                'response_code'            => (int)$this->helper->getArrayValue(
                                                                    $result['transactionResponse'],
                                                                    'responseCode'
                                                                  ),
                'response_reason_code'     =>
                (int)$this->helper->getArrayValue($result['transactionResponse'], 'errors/error/errorCode')
                ?: (int)$this->helper->getArrayValue($result['transactionResponse'], 'messages/message/code'),
                'response_reason_text'     =>
                $this->helper->getArrayValue($result['transactionResponse'], 'errors/error/errorText')
                ?: $this->helper->getArrayValue($result['transactionResponse'], 'messages/message/description'),
                'approval_code'            => $this->helper->getArrayValue($result['transactionResponse'],'authCode'),
                'auth_code'                => $this->helper->getArrayValue($result['transactionResponse'],'authCode'),
                'avs_result_code'          => $this->helper->getArrayValue($result['transactionResponse'], 'avsResultCode'),
                'transaction_id'           => $this->helper->getArrayValue($result['transactionResponse'],'transId'),
                'reference_transaction_id' => $this->helper->getArrayValue($result['transactionResponse'],'refTransId'),
                'invoice_number'           => $anet_order_number,
                'description'              => $this->modParam($store->getName().' ('.$store->getBaseUrl().')', ['maxLength' => 255]),
                'amount'                   => $cc_payment['cc_payment'],
                'method'                   => 'CC',
                'transaction_type'         => $this->txnTypeMap['authOnlyTransaction'],
                'customer_id'              => $this->cart->getQuote()->getCustomerId(),
                'card_code_response_code'  => $this->helper->getArrayValue(
                                                              $result['transactionResponse'],
                                                              'cvvResultCode'
                                                            ),
                'cavv_response_code'       => $this->helper->getArrayValue(
                                                              $result['transactionResponse'],
                                                              'cavvResultCode'
                                                            ),
                'acc_number'               => $this->helper->getArrayValue(
                                                              $result['transactionResponse'],
                                                              'accountNumber'
                                                            ),
                'card_type'                => $this->helper->getArrayValue(
                                                              $result['transactionResponse'],
                                                              'accountType'
                                                            ),
                'profile_id'               => $anet_customer_profile_id,
                'payment_id'               => $anet_customer_payment_id,
                'is_fraud'                 => false,
                'is_error'                 => false,
                ];

                $auth_result_data = [
                'tokenbase_id' => $paradoxLabs_tokenbase_id,
                'anet_order_number' => $anet_order_number,
                'cc_approval' => $this->helper->getArrayValue($result['transactionResponse'], 'authCode'),
                'cc_avs_status' => $this->helper->getArrayValue($result['transactionResponse'], 'avsResultCode'),
                'cc_cid_status' => $this->helper->getArrayValue($result['transactionResponse'], 'cvvResultCode'),
                'cc_status' => $this->helper->getArrayValue($result['transactionResponse'], 'cavvResultCode'),
                'cc_type' => $cc_payment['payment']['cc_type'],
                'cc_exp_month' => $cc_payment['payment']['cc_exp_month'],
                'cc_exp_year' => $cc_payment['payment']['cc_exp_year'],
                'cc_last_4' => $cc_payment['payment']['cc_last4'],
                'trans_id' => $this->helper->getArrayValue($result['transactionResponse'], 'transId'),
                'trans_type' => 'authorize',
                'amount_paid' =>  $cc_payment['cc_payment'],
                'additional_information' => $this->_json->serialize($additional_information)
                ];
                $this->connection->insert($split_payment_transaction_table, $auth_result_data);

              /* -------------------- add into split_payment_transaction table  END ------------------------- */

                $errorText = $this->helper->getArrayValue($result['transactionResponse'], 'errors/error/errorText');

                if ($errorText) {
                    $cardNumber = (string)$this->helper->getArrayValue($cc_payment, 'payment/cc_number');

                    $errors[] = sprintf('Card Ending in %s: %s ', substr($cardNumber, -4), $errorText);
                }
            } else {
                $is_cc_authorize_fail = 1;
                $errorAccountNumber = $this->helper->getArrayValue(
                    $result,
                    'transactionResponse/accountNumber'
                );
                $errorText = $this->helper->getArrayValue($result, 'messages/message/text');
                $errorText2 = $this->helper->getArrayValue(
                    $result,
                    'transactionResponse/errors/error/errorText'
                );
                if (!empty($errorText2)) {
                    $errorText .= ' ' . $errorText2;
                }
                break;
            }
        }

        if ($errors) {
            $errorText = 'Unable to submit payment. ';

            foreach ($errors as $error) {
                $errorText .= $error;
            }

            return $resultJson->setData(['success' => 0, 'msg' => $errorText]);
        }

      /*========================= CC Card authorization  END ====================== */


        $logger->info('-----------------------CC Card authorization  END-----------------------------------------');
        if ($is_cc_authorize_fail == 1) {
          /*======================== if any CC Card authorize fail, then void / cancel  START  ===================== */
            foreach ($authorizeTransArr as $authorizeTrans) {
                $params = [
                'transactionRequest'    => [
                  'transactionType' => 'voidTransaction',
                  'refTransId'  => $authorizeTrans['trans_id']
                ]
                ];

                $logger->info('REQUEST CODE = createTransactionRequest > createTransactionRequest');
                $logger->info('REQUEST ='.print_r($params, true));

                $result = [];
                $result = $this->runTransaction('createTransactionRequest', $params);

                $logger->info('result ='.print_r($result, true));
                $logger->info('------------------------------------------------------------------------------');

              /* ----------------------- update  split_payment_transaction table  START -------------------- */
                $capture_result_data = [
                'trans_type' => 'void',
                ];
                $where = ['trans_id = ?' => $authorizeTrans['trans_id']];
                $this->connection->update($split_payment_transaction_table, $capture_result_data, $where);
              /* -------------------- update  split_payment_transaction table  END ------------------------- */


            }
            if (!empty($errorAccountNumber)) {
                return $resultJson->setData(['success' => 0,'msg'=> 'Card number '.$errorAccountNumber.': '.$errorText ]);
            }

            $cardNumber = (string)$this->helper->getArrayValue($cc_payment, 'payment/cc_number');

            $errorTextFallback = sprintf(
                'Unable to submit payment. Card Ending in %s: This transaction has been declined.',
                substr($cardNumber, -4)
            );

            return $resultJson->setData([
                'success' => 0,
                'msg' => $errorText ?: $result['message'] ?? $errorTextFallback
            ]);

            /*=========================  if any CC Card authorize fail, then void / cancel  END  ====================== */
        } else {

          /* ============================ order create  START ======================= */
		    $quote = $this->cart->getQuote();
            $this->cart->getQuote()->getPayment()->importData(['method' => 'splitpayment']);
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals()->save();
            $order = $this->quoteManagement->submit($this->cart->getQuote());
            if ($order) {
                $order->setEmailSent(1);
                $this->_eventManager->dispatch(
                    'checkout_type_onepage_save_order_after',
                    ['order' => $order, 'quote' => $this->cart->getQuote()]
                );

                $this->checkoutSession
                ->setLastQuoteId($this->cart->getQuote()->getId())
                ->setLastSuccessQuoteId($this->cart->getQuote()->getId())
                ->clearHelperData();

                $this->checkoutSession
                ->setLastOrderId($order->getId())
                //->setRedirectUrl($redirectUrl)
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

                $this->_eventManager->dispatch(
                    'checkout_submit_all_after',
                    [
                    'order' => $order,
                    'quote' => $this->cart->getQuote()
                    ]
                );

            }
          /* ============================ order create  END ======================= */

          /*========================= CC Card Capture Transaction  START  ====================== */
            foreach ($authorizeTransArr as $authorizeTrans) {
                $params = [
                'transactionRequest'    => [
                'transactionType' => 'priorAuthCaptureTransaction',
                'amount' => $authorizeTrans['amount'],
                'refTransId'  => $authorizeTrans['trans_id']
                ]
                ];

                $logger->info('REQUEST CODE = createTransactionRequest > priorAuthCaptureTransaction');
                $logger->info('REQUEST ='.print_r($params, true));

                $result = [];
                $result = $this->runTransaction('createTransactionRequest', $params);

                $logger->info('result ='.print_r($result, true));
                $logger->info('------------------------------------------------------------------------------');

                if (!empty($result['messages']['resultCode']) && $result['messages']['resultCode'] == 'Ok') {
                  /* ----------------------- update  split_payment_transaction table  START -------------------- */

                    $capture_result_data = [
                    'order_id' => $order->getId(),
                    //'cc_avs_status' => $this->helper->getArrayValue($result['transactionResponse'], 'avsResultCode'),
                    'trans_type' => 'capture',
                    //'additional_information' => $this->getDataFromTransactionResponse($result['transactionResponse'])
                    ];
                    $where = ['trans_id = ?' => $authorizeTrans['trans_id']];
                    $this->connection->update($split_payment_transaction_table, $capture_result_data, $where);

                  /* -------------------- update  split_payment_transaction table  END ------------------------- */

                }
            }
            $logger->info('========================================================================');
            return $resultJson->setData(['success' => 1,'msg'=> '']);
          /*========================= CC Card Capture Transaction  END  ====================== */
        }
    }

    protected function runTransaction($request, $params)
    {
        $auth = [
          '@attributes'            => [
              'xmlns' => 'AnetApi/xml/v1/schema/AnetApiSchema.xsd',
          ],
          'merchantAuthentication' => [
              'name'           => $this->getConfigValue('payment/authnetcim/login'),
              'transactionKey' => $this->getConfigValue('payment/authnetcim/trans_key'),
          ],
        ];

        $xml = $this->arrayToXml($request, $auth + $params);

        $this->lastRequest = $xml;

        /** @var \Magento\Framework\HTTP\Client\Curl|\Magento\Framework\HTTP\Client\Socket $communicator */
        $communicator = $this->communicatorFactory->create();

        // If we are running a money transaction, we don't want to cut it off even if it takes too long.
        // Override that 900 second timeout only if this is a non-critical transaction.
        $communicator->setTimeout(900);
        if (!in_array($request, ['createTransactionRequest', 'createCustomerProfileTransactionRequest'])) {
            $communicator->setTimeout(15);
        }

        $communicator->setOption(\CURLOPT_SSL_VERIFYPEER, false);
        $communicator->setOption(\CURLOPT_SSL_VERIFYHOST, 0);
        if ($this->getConfigValue('payment/authnetcim/verify_ssl') == true) {
            $certificatePath = $this->moduleDir->getDir('ParadoxLabs_Authnetcim') . '/authorizenet-cert.pem';

            $communicator->setOption(\CURLOPT_SSL_VERIFYPEER, true);
            $communicator->setOption(\CURLOPT_SSL_VERIFYHOST, 2);
            $communicator->setOption(\CURLOPT_CAINFO, $certificatePath);
        }

        $communicator->addHeader('Content-Type', 'text/xml');

        try {
            if ($this->getConfigValue('payment/authnetcim/test') == 1) {
                $this->endpoint = $this->endpointTest;
            } else {
                $this->endpoint = $this->endpointLive;
            }

            $communicator->post($this->endpoint, $xml);

            $this->lastResponse = $communicator->getBody();

            if (!empty($this->lastResponse)) {
                $this->log .= 'REQUEST: ' . $this->sanitizeLog($xml) . "\n";
                $this->log .= 'RESPONSE: ' . $this->sanitizeLog($this->lastResponse) . "\n";

                $this->lastResponse = $this->xmlToArray($this->lastResponse);

                if ($this->testMode === true) {
                    $this->helper->log($this->code, $this->log, true);
                }

                /**
                 * Check for basic errors.
                 */
                $cardNumber = (string)$this->helper->getArrayValue($params, 'transactionRequest/payment/creditCard/cardNumber');

                $this->handleTransactionError($cardNumber);
            } else {
                $this->helper->log(
                    $this->code,
                    sprintf(
                        "Connection failed, empty response\nREQUEST: %s",
                        $this->sanitizeLog($xml)
                    )
                );

                //return array('error'=> 1,'message' => 'Authorize.Net CIM Gateway Connection failed');
            }
        } catch (\Exception $exception) {
            $this->helper->log(
                $this->code,
                sprintf(
                    "CURL Connection error: %s\nREQUEST: %s",
                    $exception->getMessage(),
                    $this->sanitizeLog($xml)
                )
            );

            return ['error' => 1, 'message' => $exception->getMessage()];
        }

        return $this->lastResponse;
    }

  /**
   * Mask certain values in the XML for secure logging purposes.
   *
   * @param string $string
   * @return mixed
   */
    protected function sanitizeLog($string)
    {
        $maskAll = ['cardCode'];
        $maskFour = ['cardNumber', 'name', 'transactionKey', 'routingNumber', 'accountNumber'];

        foreach ($maskAll as $val) {
            $string = preg_replace('#' . $val . '>(.+?)</' . $val . '#', $val . '>XXX</' . $val, (string)$string);
        }

        foreach ($maskFour as $val) {
            $start = strpos($string, '<' . $val . '>');
            $end = strpos($string, '</' . $val . '>', $start);
            $tagLen = strlen($val) + 2;

            if ($start !== false && $end > ($start + $tagLen + 4)) {
                $string = substr_replace($string, 'XXXX', $start + $tagLen, $end - 4 - ($start + $tagLen));
            }
        }

        return $string;
    }

    protected function xmlToArray($xml)
    {
        // Strip bad namespace out before we try to parse it. ...
        $xml = str_replace(' xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $xml);

        try {
            return $this->xml->createArray($xml);
        } catch (\Exception $e) {
            $this->helper->log($this->code, $e->getMessage() . "\n" . $this->sanitizeLog($xml));

            //throw $e;
            //return array('error'=> 1,'message' => $e->getMessage());
        }
    }

    protected function arrayToXml($rootName, $array)
    {
        $xml = $this->xml->createXML($rootName, $array);

        return $xml->saveXML();
    }

    protected function handleTransactionError(string $cardNumber)
    {
        if (mb_strtoupper($this->lastResponse['messages']['resultCode']) !== self::RESULT_CODE_OK ||
            mb_strtoupper($this->lastResponse['messages']['message']['code']) !== self::CODE_I00001_SUCCESSFULL) {
            $errorCode = $this->helper->getArrayValue($this->lastResponse, 'messages/message/code');
            $errorText = $this->helper->getArrayValue($this->lastResponse, 'messages/message/text');
            $errorText2 = $this->helper->getArrayValue(
                $this->lastResponse,
                'transactionResponse/errors/error/errorText'
            );

            if (!empty($errorText2)) {
                $errorText .= ' ' . $errorText2;
            }

            /**
             * Log and spit out generic error. Skip certain warnings we can handle.
             */
            $okayErrorCodes = ['E00039', 'E00040'];
            $okayErrorTexts = [
              'The referenced transaction does not meet the criteria for issuing a credit.',
              'The transaction cannot be found.',
            ];

            if (!empty($errorCode)
              && !in_array($errorCode, $okayErrorCodes, true)
              && !in_array($errorText, $okayErrorTexts, true)
              && !in_array($errorText2, $okayErrorTexts, true)
            ) {
                $this->helper->log(
                    $this->code,
                    sprintf("API error: %s: %s\n%s", $errorCode, $errorText, $this->log)
                );

                if ($errorText === 'Invalid OTS Token.') {
                    $errorText = 'Invalid token. Please re-enter your payment info.';
                }

                /*throw new CommandException(
                  __(sprintf('Authorize.Net CIM Gateway111: %s (%s)', $errorText, $errorCode))
                );*/
                //return array('error'=> 1,'message' => 'Authorize.Net CIM Gateway: '.$errorText.' ('.$errorCode.')');
            }

            throw new LocalizedException(__(sprintf(
                'Unable to submit payment. Card ending in %s: %s',
                substr($cardNumber, -4),
                html_entity_decode($errorText, ENT_QUOTES) // Decode HTML entities safely
            )));
        }
    }

    public function modParam($val, $format = [])
    {
        if (!empty($val)) {
            if (isset($format['noSymbols']) && $format['noSymbols'] === true) {
                /**
                 * Convert special characters to simple ascii equivalent
                 */
                $val = htmlentities((string)$val, ENT_QUOTES, 'UTF-8');
                $val = preg_replace(
                    '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i',
                    '$1',
                    $val
                );
                  $val = preg_replace(['/[^0-9a-z \.\-]/i', '/\s{2,}/'], ' ', $val);
                  $val = trim($val);
            }

            if (isset($format['charMask'])) {
                /**
                 * Apply a regex character filter to the input.
                 */
                $val = preg_replace('/[^' . $format['charMask'] . ']/', '', (string)$val);
            }

            if (isset($format['maxLength']) && $format['maxLength'] > 0) {
              /**
               * Truncate if the value is too long
               */
                $val = substr((string)$val, 0, $format['maxLength']);
            } elseif (isset($format['enum'])) {
              /**
               * Error if value is not on the allowed list
               */
                if (in_array($val, $format['enum'])) {
                    $val = $val;
                }
            } else {
                $val = $val;
            }

        }

        return $val;
    }

    /**
     * @var array
     */
    protected $txnTypeMap = [
      'authCaptureTransaction'      => 'auth_capture',
      'authOnlyTransaction'         => 'auth_only',
      'captureOnlyTransaction'      => 'capture_only',
      'priorAuthCaptureTransaction' => 'prior_auth_capture',
      'refundTransaction'           => 'credit',
      'voidTransaction'             => 'void',
    ];

    public function formatAmount($amount)
    {
        return sprintf('%01.2f', (float) $amount);
    }

    public function getConfigValue($store_config_field)
    {
        return $this->scopeConfig->getValue(
            $store_config_field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }
}
