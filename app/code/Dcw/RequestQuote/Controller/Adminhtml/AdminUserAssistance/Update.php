<?php
namespace Dcw\RequestQuote\Controller\Adminhtml\AdminUserAssistance;

use \Magento\Framework\Controller\ResultFactory;
use Dcw\RequestQuote\Service\AdminQuotePermissionService;
use Magento\Framework\Exception\LocalizedException;

class Update extends \Magento\Backend\App\Action
{
    protected $resource;
    protected $permissionService;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\ResourceConnection $resource,
        AdminQuotePermissionService $permissionService
    ) {
        $this->resource = $resource;
        $this->permissionService = $permissionService;
        parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        try {
            // Check permission
            if (!$this->permissionService->canEditAdminAssistance()) {
                throw new LocalizedException(
                    __('You do not have permission to edit Admin User Assistance.')
                );
            }
          
            $connection = $this->resource->getConnection();
            $quote_table = $connection->getTableName("quote");

            $where = ['entity_id = ?' => $post['quote_id']];
            $connection->update($quote_table, ['admin_user_assistance' => $post['admin_user_assistance']], $where);

            $this->messageManager->addSuccess(__('Admin User Assistance has been updated.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    protected function _isAllowed()
    {
        return true;
    }
}