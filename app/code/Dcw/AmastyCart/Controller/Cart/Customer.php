<?php

namespace Dcw\AmastyCart\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;;

class Customer extends \Magento\Framework\App\Action\Action
{
	public $connection;
	protected $customerRepository;
    public function __construct(
        Context $context,
        AccountManagementInterface $customerAccountManagement,
        StoreManagerInterface $storeManager,
        ResultFactory $resultFactory,
        Session $customerSession,
        RawFactory $resultRawFactory,
        ManagerInterface $managerInterface,
		\Magento\Framework\App\ResourceConnection $connection,
		\Magento\Customer\API\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->customerAccountManagement = $customerAccountManagement;
        $this->storeManager = $storeManager;
        $this->resultFactory = $resultFactory;
        $this->customerSession = $customerSession;
        $this->resultRawFactory = $resultRawFactory;
        $this->managerInterface = $managerInterface;
		$this->connection = $connection;
		$this->customerRepository = $customerRepository;
    }

    public function execute()
    {
        $email = $this->getRequest()->getParam('email');
        $password = '';

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if ($this->getRequest()->getParam('password')) {
            $password = $this->getRequest()->getParam('password');
        }

        $action = $this->getRequest()->getParam('submit_action');

        $websiteId = (int)$this->storeManager->getWebsite()->getId();
		$isEmailExist = $this->customerAccountManagement->isEmailAvailable($email, $websiteId);
		
		switch ($action) {
            case 'check_email':
				$registerwith = "";
				$isvalid = $this->_checkEmail($isEmailExist);
				if($isvalid['message'] == "Email exists"){
					 $connection = $this->connection->getConnection();
					 $customerquery =  $connection->select()->from(['main_table' =>$this->connection->getTableName('customer_entity')],
                    ['entity_id'] )->where('email = ?', $email);
					 
					 $customerID = $connection->fetchOne($customerquery);
					 
					 $select = $connection->select()->from(['main_table' =>$this->connection->getTableName('bss_sociallogin')],
                    ['type'] )
                ->where('customer_id = ?', $customerID);
					$registerwith = $connection->fetchOne($select);
				}
				
				if($registerwith!=""){
					  $data = [
						'message' => 'Email exists with '.$registerwith,
						'email' => !$isEmailExist
					  ]; 
					$resultJson->setData($data);
				} else {
					$resultJson->setData($this->_checkEmail($isEmailExist));
				}
				
				return $resultJson;
            case 'login_customer':
                $resultJson->setData($this->_loginCustomer($email, $password));
                return $resultJson;
            default:
                return null;
        }
    }

    private function _checkEmail($isEmailExist)
    {
        if ($isEmailExist) {
            $data = [
                'message' => 'Email does not exists',
                'email' => !$isEmailExist
            ];
        } else {
            $data = [
                'message' => 'Email exists',
                'email' => !$isEmailExist
            ];
        }

        return $data;
    }

    private function _loginCustomer($email, $password)
    {
        $credentials = null;
        $httpBadRequestCode = 400;

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        try {
            $credentials = [
                'username' => $email,
                'password' => $password
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'message' => __('Login successful.')
        ];
        try {
            $customer = $this->customerAccountManagement->authenticate(
                $credentials['username'],
                $credentials['password']
            );
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
            $this->customerSession->regenerateId();
            $this->managerInterface->addSuccessMessage(__("You are logged in."));
        } catch (EmailNotConfirmedException $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        } catch (InvalidEmailOrPasswordException $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'message' => __('Invalid login or password.').$e->getMessage()
            ];
        }
        return $response;
    }
	
	 public function getCustomerByEmail($email)
    {
        $customerId = null;
		try {
            // $websiteId = 1;
            $customer = $this->customerRepository->get($email);
			
        } catch (Exception $e) {
        }
		
		return $customerId;
    }
}
