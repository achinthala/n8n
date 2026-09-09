<?php
namespace Dcw\PaymentMethods\Block;

class Index extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminIdInterface,
        array $data = []
    ) {
        $this->getLoggedAsCustomerAdminIdInterface = $getLoggedAsCustomerAdminIdInterface;
        parent::__construct($context, $data);
    }
    
    public function getAdminIdLoggedAsCustomer()
    {
        return  $this->getLoggedAsCustomerAdminIdInterface->execute();
    }
}
