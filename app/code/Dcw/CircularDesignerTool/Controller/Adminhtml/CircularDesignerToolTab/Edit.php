<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerToolTab;

/**
 * Edit CircularDesignerTool action.
 */

class Edit extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerToolTab
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $storeViewId = $this->getRequest()->getParam('store');
        $model = $this->_circulardesignertooltabFactory->create();

        if ($id) {
            $model->setStoreViewId($storeViewId)->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This CircularDesignerTool Tab no longer exists.'));
                $resultRedirect = $this->_resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('circulardesignertooltab', $model);

        $resultPage = $this->_resultPageFactory->create();

        return $resultPage;
    }
}
