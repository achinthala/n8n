<?php
namespace Dcw\ShoppingCart\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;

class AddBodyClass implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\View\Page\Config $pageConfig
    ) {
        $this->_pageConfig = $pageConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if(isset($_REQUEST['spotlight']) && $_REQUEST['spotlight']!='') { 
           $this->_pageConfig->addBodyClass('spotlight-body'); 
        }
         
    }
}
