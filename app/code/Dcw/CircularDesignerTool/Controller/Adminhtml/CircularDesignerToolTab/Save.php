<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerToolTab;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Save CircularDesignerTool action.
 */

class Save extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerToolTab
{
    public function execute()
    {
        $resultRedirect = $this->_resultRedirectFactory->create();

        if ($data = $this->getRequest()->getPostValue()) {
            $model = $this->_circulardesignertooltabFactory->create();
            $storeViewId = $this->getRequest()->getParam('store');

            if ($id = $this->getRequest()->getParam('id')) {
                $model->load($id);
            }

            try {
        
                $model->setData($data)
                  ->setStoreViewId($storeViewId)
                   ->setId($id);
                $model->save();

                $this->messageManager->addSuccess(__('The circulardesignertool tab has been saved.'));
                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back') === 'edit') {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        [
                            'id' => $model->getId(),
                            '_current' => true,
                            'store' => $storeViewId,
                            'saveandclose' => $this->getRequest()->getParam('saveandclose'),
                        ]
                    );
                } elseif ($this->getRequest()->getParam('back') === 'new') {
                    return $resultRedirect->setPath(
                        '*/*/new',
                        ['_current' => true]
                    );
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->messageManager
                ->addException($e, __('Something went wrong while saving the circulardesignertool.'));
            }

            $this->_getSession()->setFormData($data);

            return $resultRedirect->setPath(
                '*/*/edit',
                ['id' => $this->getRequest()->getParam('id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}
