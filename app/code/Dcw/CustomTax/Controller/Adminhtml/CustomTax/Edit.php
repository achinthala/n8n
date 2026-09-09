<?php
namespace Dcw\CustomTax\Controller\Adminhtml\CustomTax;

/**
 * Edit CustomTax action.
 */

class Edit extends \Dcw\CustomTax\Controller\Adminhtml\CustomTax
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $storeViewId = $this->getRequest()->getParam('store');
        $model = $this->_customtaxFactory->create();

        if ($id) {
            $model->setStoreViewId($storeViewId)->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This CustomTax no longer exists.'));
                $resultRedirect = $this->_resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('customtax', $model);

        $resultPage = $this->_resultPageFactory->create();

        return $resultPage;
    }
}
