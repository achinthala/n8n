<?php

namespace Dcw\RequestQuote\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\User\Model\UserFactory;


class Data extends AbstractHelper
{
    
	protected $getLoggedAsCustomerAdminIdInterface;
	protected $userFactory;
	
    public function __construct(
        Context $context,
		\Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminIdInterface,
		UserFactory $userFactory,
       
    ) {
        parent::__construct($context);
        $this->getLoggedAsCustomerAdminIdInterface = $getLoggedAsCustomerAdminIdInterface;
		$this->userFactory = $userFactory;
    }
	
	
	public function getAdminIdLoggedAsCustomer()
    {
        $adminId =  $this->getLoggedAsCustomerAdminIdInterface->execute();
        if(!empty($adminId) && ($adminId!=0)){
            $adminUser = $this->userFactory->create()->load($adminId);

            // Get the admin username
            $adminUsername = $adminUser->getUsername();

        return $adminUsername;
        }else{
            return '';
        }
    }
	
	
	
}