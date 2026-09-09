<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool;

/**
 * New CircularDesignerTool action.
 */

class NewAction extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool
{
    public function execute()
    {
        $resultForward = $this->_resultForwardFactory->create();

        return $resultForward->forward('edit');
    }
}
