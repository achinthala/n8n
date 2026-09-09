<?php
namespace Dcw\SplitPayment\Controller\CheckGiftCardBalance;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $splitPaymentBlock;
    protected $resultPageFactory;
    protected $managerInterface;
    protected $_resultJsonFactory;
    protected $cart;
    protected $curl;
    protected $_json;
    protected $moduleDir;

    public function __construct(
        Context $context,
        \Dcw\SplitPayment\Block\Index $splitPaymentBlock,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\Module\Dir $moduleDir
    ) {
        $this->splitPaymentBlock = $splitPaymentBlock;
        $this->resultPageFactory = $resultPageFactory;
        $this->managerInterface = $managerInterface;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->cart = $cart;
        $this->curl = $curl;
        $this->_json = $json;   
        $this->moduleDir = $moduleDir;
        parent::__construct($context);
    }
    
    public function execute()
    {
        /*$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(__METHOD__);*/

        $resultJson = $this->_resultJsonFactory->create();
        $post = $this->getRequest()->getPostValue();
        //print_r($post);
        $grand_total = $this->cart->getQuote()->getGrandTotal();
        try {
            $moduleViewPath = $this->moduleDir->getDir('Dcw_SplitPayment', \Magento\Framework\Module\Dir::MODULE_VIEW_DIR);
            $p12File = $moduleViewPath.'/frontend/web/curl/certificate.pem'; 

            $blackhawk_api_url = $this->splitPaymentBlock->getConfigValue('payment/splitpayment/blackhawk_api_url');
            $blackhawk_product_line_id = $this->splitPaymentBlock->getConfigValue('payment/splitpayment/blackhawk_product_line_id');
            $blackhawk_account_type = $this->splitPaymentBlock->getConfigValue('payment/splitpayment/blackhawk_account_type');

            $url = $blackhawk_api_url.'/accountProcessing/v1/verifyAccount?accountNumber='.$post["gc_card_number"].'&productLineId='.$blackhawk_product_line_id.'&accountType='.$blackhawk_account_type.'&cvv2='.$post["gc_cvv2"].'&expirationDate='.$post["gc_exp_date"];
            $p12Password = '5R2VSM92C8K144635V85S6BP0M';

            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
            $this->curl->setOption(CURLOPT_SSLCERT, $p12File);
            $this->curl->setOption(CURLOPT_SSLCERTPASSWD, $p12Password);
            $this->curl->setOption(CURLOPT_SSLKEYTYPE, 'PEM');
            $this->curl->get($url);

            $response = $this->curl->getBody();
            $responseArr = $this->_json->unserialize($response);

            //$logger->info('responseArr ='.print_r($responseArr,true));

            if (empty($responseArr['errorCode']) && 
            (!empty($responseArr['status']) && $responseArr['status'] == 'ACTIVATED')) {
                $balance = $responseArr['balance'];
                $response = $resultJson->setData(['success' => 1, 'balance' => $balance, 'grand_total' => $grand_total]);
            } else {
                if (!empty($responseArr['errorCode']) && !empty($responseArr['message'])) {
                    $response = $resultJson->setData(['success' => 0, 'message' => $responseArr['message'].': '.$responseArr['errorCode']]);
                } else {
                    $response = $resultJson->setData(['success' => 1, 'message' => 'There is someting error while performing the operation']);
                }
            }
            
        } catch (\Exception $e) {
            $response = $resultJson->setData(['success' => 0,'message'=>$e->getMessage()]);
        }
        return $response;
    }
}
