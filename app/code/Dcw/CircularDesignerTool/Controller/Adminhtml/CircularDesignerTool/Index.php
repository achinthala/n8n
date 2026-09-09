<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool;

/**
 * Index action.
 */

class Index extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            
            $resultForward = $this->_resultForwardFactory->create();
            $resultForward->forward('grid');

            return $resultForward;
        }

        $resultPage = $this->_resultPageFactory->create();

        $this->_addBreadcrumb(__('CircularDesignerTool'), __('CircularDesignerTool'));
        $this->_addBreadcrumb(__('Manage CircularDesignerTool'), __('Manage CircularDesignerTool'));

        return $resultPage;
    }
}
