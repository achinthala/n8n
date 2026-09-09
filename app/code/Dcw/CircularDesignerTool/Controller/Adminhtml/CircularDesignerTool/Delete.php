<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool;

/**
 * Delete CircularDesignerTool action
 */

class Delete extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool
{
    public function execute()
    {
        $Id = $this->getRequest()->getParam('id');
        try {
            $circulardesignertool = $this->_circulardesignertoolFactory->create()->setId($Id);
            $circulardesignertool->delete();
            $this->messageManager->addSuccess(
                __('Delete successfully !')
            );
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        $resultRedirect = $this->_resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
