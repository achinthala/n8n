<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Controller\Adminhtml\Enquiry;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ProductEnquiry::enquiry';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_ProductEnquiry::enquiry');
        $resultPage->getConfig()->getTitle()->prepend(__('Product Enquiries'));

        return $resultPage;
    }
}
