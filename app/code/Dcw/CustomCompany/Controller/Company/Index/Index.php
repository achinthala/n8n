<?php
namespace Dcw\CustomCompany\Controller\Company\Index;

class Index extends \Magento\Company\Controller\Index\Index
{
    
     /**
      * Company Auction
      *
      * @return Forward|Redirect
      */

    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->loadLayoutUpdates();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Pro Advantage'));
        $this->_view->renderLayout();
    }
}
