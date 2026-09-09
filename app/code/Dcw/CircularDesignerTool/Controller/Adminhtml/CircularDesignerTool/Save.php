<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Save CircularDesignerTool action.
 */

class Save extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool
{
    public function execute()
    {
        $resultRedirect = $this->_resultRedirectFactory->create();

        if ($data = $this->getRequest()->getPostValue()) {
            $model = $this->_circulardesignertoolFactory->create();
            $storeViewId = $this->getRequest()->getParam('store');

            if ($id = $this->getRequest()->getParam('id')) {
                $model->load($id);
            }

            /*==================== image upload START ==========================*/
            $files = $this->getRequest()->getFiles()->toArray();
            if (isset($files['image']) && isset($files['image']['name']) && strlen($files['image']['name'])) {
                try {
                    $base_media_path = 'designer_tool/';
                    $upload = $this->uploaderFactory->create(
                        ['fileId' => 'image']
                    );
                    $upload->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                    $upload->addValidateCallback('image', $this->imageAdapter, 'validateUploadFile');
                    $upload->setAllowRenameFiles(true);
                    $upload->setFilesDispersion(false);
                    $mediaDirectory = $this->filesystem
                    ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                    $result = $upload->save(
                    //$mediaDirectory->getAbsolutePath()
                        $mediaDirectory->getAbsolutePath($base_media_path)
                    );
                    
                    $data['image'] = $result['file'];
                
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                }
            } else {
                if (isset($data['image']) && isset($data['image']['value'])) {
                //$data['image']['value'];
                    $image_val = str_replace("designer_tool/", "", $data['image']['value']);
                
                    if (isset($data['image']['delete'])) {
                        $mediaDirectory = $this->filesystem
                        ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                
                        $pathToFile = $mediaDirectory->getAbsolutePath().$data['image']['value'];
                        if ($this->file->fileExists($pathToFile)) {
                            $this->file->rm($pathToFile);
                        }
                
                        $data['image'] = '';
                        $data['delete_image'] = true;
                    } elseif (isset($image_val)) {
                        $data['image'] = $image_val;
                    } else {
                        $data['image'] = null;
                    }
                }
            }
            /*==================== image upload END ==========================*/
            try {
                $model->setData($data)
                ->setStoreViewId($storeViewId)
                ->setId($id);
                $model->save();
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->messageManager
                ->addException($e, __('Something went wrong while saving the circulardesignertool.'));
            }

            $this->messageManager->addSuccess(__('The circulardesignertool has been saved.'));
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

           //return $resultRedirect->setPath('*/*/');
        
            $this->_getSession()->setFormData($data);

            return $resultRedirect->setPath(
                '*/*/edit',
                ['id' => $this->getRequest()->getParam('id')]
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}
