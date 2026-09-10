<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ProductDataValidation::view';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_ProductDataValidation::report');
        $resultPage->getConfig()->getTitle()->prepend(__('Product Data Validation Report'));

        return $resultPage;
    }
}
