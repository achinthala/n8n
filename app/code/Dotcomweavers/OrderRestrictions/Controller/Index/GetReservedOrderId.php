<?php
namespace Dotcomweavers\OrderRestrictions\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use Dotcomweavers\OrderRestrictions\Model\ResourceModel\Rule\CollectionFactory as RulesCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class GetReservedOrderId extends \Magento\Framework\App\Action\Action
{
    protected $_storeManagerInterface;
    protected $rulesCollectionFactory;
    protected $_customerSession;
    protected $quoteFactory;
    protected $scopeConfig;
    protected $_resultJsonFactory;
    protected $resource;
    protected $connection;
    protected $cart;
    protected $addressRepository;
    protected $regionFactory;
    protected $checkoutSession;

    public const XML_PATH_CUSTOMER_FLAG = 'orderrestrictions/general/enable_admin_login_as_customer';

	public function __construct(
		Context $context,
        StoreManagerInterface $storeManagerInterface,
        RulesCollectionFactory $rulesCollectionFactory,
        Session $customerSession,
        QuoteFactory $quoteFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        CheckoutSession $checkoutSession
	) {
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->quoteFactory = $quoteFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->cart = $cart;
        $this->addressRepository = $addressRepository;
        $this->regionFactory = $regionFactory;
        $this->checkoutSession = $checkoutSession;
		parent::__construct($context);
    }
	
	public function execute()
    {
        $quoteId = $this->cart->getQuote()->getId();
        $post = $this->getRequest()->getPostValue();

        $ruleResult = '';
        $ruleResultMsg = '';
		$reserveID = "";

        if ($quoteId) {
            
            $quote = $this->quoteFactory->create()->load($quoteId);
			if($quote->getReservedOrderId()!=""){
				$reserveID = $quote->getReservedOrderId();
			}
            
        }

        $output = [
			'reserve_order_id' => $reserveID
        ];
    
        $resultJson = $this->_resultJsonFactory->create();
        $result = $resultJson->setData($output);
        
        $shippingAddress = $this->cart->getQuote()->getShippingAddress();
        $customerAddressId = $this->getRequest()->getParam('customerAddressId');

        if($customerAddressId!=$shippingAddress->getCustomerAddressId()){
            $this->createLog("Address Has been Changed . Default Shipping Address id ".$shippingAddress->getCustomerAddressId());
            //reset checkout session of custom shipping amount
            try {
                // Check if "amasty_quote_shipping_amount" is set in session
                if ($this->checkoutSession->getAmastyQuoteShippingAmount()) {
                    $this->createLog("Session is set");
                        // Unset the session variable
                        $this->checkoutSession->unsAmastyQuoteShippingAmount();
                    }else{
                        $this->createLog("Session is not set");
                    }
                } catch (\Exception $e) {
                    // Log the exception message
                    $this->createLog('Error while unsetting amasty_quote_shipping_amount: ' . $e->getMessage());
                }
            }
        return $result;
	}

    public function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/GetReserverOrderId.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}