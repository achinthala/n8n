<?php

namespace Dcw\PaymentMethods\Observer;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class PaymentMethodDisable implements ObserverInterface
{
    /**
     * Payment methods restricted to admin only (disabled on frontend)
     */
    private const ADMIN_ONLY_METHODS = ['wirepayment', 'checkmo'];

    protected $dcwPaymentBlock;
    protected $customerSession;
    protected $companyManagement;
    protected $logger;
    protected $getLoggedAsCustomerAdminIdInterface;
    protected $appState;

    public function __construct(
        \Dcw\PaymentMethods\Block\Index $dcwPaymentBlock,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        LoggerInterface $logger,
        \Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminIdInterface,
        State $appState
    ) {
        $this->dcwPaymentBlock = $dcwPaymentBlock;
        $this->customerSession = $customerSession;
        $this->companyManagement = $companyManagement;
        $this->logger = $logger;
        $this->getLoggedAsCustomerAdminIdInterface = $getLoggedAsCustomerAdminIdInterface;
        $this->appState = $appState;
    }

    /**
     * Disable wirepayment and checkmo on frontend; enable only in admin
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $methodCode = $observer->getEvent()->getMethodInstance()->getCode();

        // WirePayment and CheckMo: frontend = disabled, admin = enabled
        if (in_array($methodCode, self::ADMIN_ONLY_METHODS, true)) {
            try {
                if ($this->appState->getAreaCode() !== Area::AREA_ADMINHTML) {
                    $observer->getEvent()->getResult()->setData('is_available', false);
                }
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $observer->getEvent()->getResult()->setData('is_available', false);
                return;
            }
        }

        if ($this->customerSession->isLoggedIn()) {
            try {
				$adminId =  $this->getLoggedAsCustomerAdminIdInterface->execute();
				if($observer->getEvent()->getMethodInstance()->getCode()=="cashondelivery"){
					if(!empty($adminId) && ($adminId!=0)){
						$checkResult = $observer->getEvent()->getResult();
                        $checkResult->setData('is_available', true);
					} else {
						$checkResult = $observer->getEvent()->getResult();
                        $checkResult->setData('is_available', false);
						
					}
					
				}
				
				$_company = $this->companyManagement->getByCustomerId($this->customerSession->getCustomer()->getId());

                if (empty($_company)) {
                    if ($observer->getEvent()->getMethodInstance()->getCode()=="authnetcim_ach" ||
                    $observer->getEvent()->getMethodInstance()->getCode()=="wirepayment" ||
                    $observer->getEvent()->getMethodInstance()->getCode()=="purchaseorder") {
                        $checkResult = $observer->getEvent()->getResult();
                        $checkResult->setData('is_available', false);
                    }
                }
            } catch (Exception $e) {
                //$this->logger->info('An error occurred: ' . $e->getMessage());
                //do nothing
            }
        } else {
            if ($observer->getEvent()->getMethodInstance()->getCode()=="authnetcim_ach" ||
            $observer->getEvent()->getMethodInstance()->getCode()=="wirepayment" ||
            $observer->getEvent()->getMethodInstance()->getCode()=="purchaseorder" ||
            $observer->getEvent()->getMethodInstance()->getCode()=="cashondelivery") {
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('is_available', false);
            }
        }
    }
}
