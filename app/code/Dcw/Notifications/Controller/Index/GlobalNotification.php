<?php
/**
 *  @author Dcw Team
 */
namespace Dcw\Notifications\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class GlobalNotification extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Dcw\Notifications\Block\Notification
     */
    protected $notificationsBlock;

    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Dcw\Notifications\Block\Notification $notificationsBlock
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->notificationsBlock = $notificationsBlock;
    }
    
    /**
     * Show Global notification on top page green section with ajax
     */
    public function execute()
    {
        $notification_text = '';
        $resp_arr = $this->notificationsBlock->getAllData4();

        //print_r($resp_arr);
        if($resp_arr["active"] == 1)
        {
            if ($resp_arr['full_action_name'] == 'catalog_product_view' || $resp_arr['full_action_name'] == 'catalog_category_view' ) {
                if ($resp_arr['full_action_name'] == 'catalog_category_view' && $resp_arr['category_notification_bar4']!='') {
                    $notification_text = $resp_arr['category_notification_bar4'];
                
                 }
                if ($resp_arr['full_action_name'] == 'catalog_product_view' && $resp_arr['incstores_banner_marketing_external']!='') { 
                    $notification_text = $resp_arr['incstores_banner_marketing_external'];
                } 
            } else { 
                    if($resp_arr['glob_notif_4']!='') { 
                        $notification_text = $resp_arr['glob_notif_4'];
                    } 
            } 
        }
        $output = [
        //'active' => $resp_arr["active"],
        'notification_text' => $notification_text,   
        ];
        
        $resultJson = $this->_resultJsonFactory->create();
        $result = $resultJson->setData($output);

        return $result;
    }
}
