<?php
namespace Dcw\Notifications\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Show extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
            parent::__construct($context);
            $this->_scopeConfig = $scopeConfig;
            $this->_resultJsonFactory = $resultJsonFactory;
    }
    
    public function execute()
    {
        $availability = $this->_scopeConfig->getValue(
            'dcw_notifications_config/notification/availability',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $store_phone = $this->_scopeConfig
        ->getValue('general/store_information/phone', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $customer_support_email = $this->_scopeConfig
        ->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        if ($availability == 1) {
            $left_notification_html = '<a class="ques-alt">Questions?</a>
            <b class="ph-icon-alt"><a>'.$store_phone.'</a></b>';
        } else {
            $left_notification_html = '<a class="email_icon" href="mailto:'.$customer_support_email.'">'.
            $customer_support_email.
            '</a>';
        }
        $right_notification_html = '<a class="email_icon" href="/contact">'.
        $customer_support_email.
        '</a>';
        $output = [
        'availability_notification' => $availability,
            'left_notification_html' => $left_notification_html,
            'right_notification_html' => $right_notification_html
            
        ];
        
        $resultJson = $this->_resultJsonFactory->create();
        $result = $resultJson->setData($output);

        return $result;
    }
}
