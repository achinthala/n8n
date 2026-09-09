<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerToolTab;

/**
 * Index action.
 */

class Index extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerToolTab
{
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();

        $this->_addBreadcrumb(__('CircularDesignerTool'), __('CircularDesignerTool'));
        $this->_addBreadcrumb(__('Manage CircularDesignerTool Tab'), __('Manage CircularDesignerTool Tab'));

        return $resultPage;
    }
}
