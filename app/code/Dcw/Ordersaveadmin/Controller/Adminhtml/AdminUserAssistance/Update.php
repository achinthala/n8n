<?php
namespace Dcw\Ordersaveadmin\Controller\Adminhtml\AdminUserAssistance;

use \Magento\Framework\Controller\ResultFactory;

class Update extends \Magento\Backend\App\Action
{
    protected $orderFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        try {
            $order = $this->orderFactory->create()->load($post['order_id']);
            $order->setData('admin_user_assistance', $post['admin_user_assistance']);
            $order->save();
            $this->messageManager->addSuccess(__('Admin User Assistance has been updated.'));
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