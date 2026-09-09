<?php
namespace Dcw\CustomTax\Controller\Adminhtml\CustomTax;

/**
 * Index action.
 */

class Index extends \Dcw\CustomTax\Controller\Adminhtml\CustomTax
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            
            $resultForward = $this->_resultForwardFactory->create();
            $resultForward->forward('grid');

            return $resultForward;
        }

        $resultPage = $this->_resultPageFactory->create();

        $this->_addBreadcrumb(__('CustomTax'), __('CustomTax'));
        $this->_addBreadcrumb(__('Manage CustomTax'), __('Manage CustomTax'));

        return $resultPage;
    }
}
