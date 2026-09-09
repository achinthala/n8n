<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Controller\Adminhtml\History;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ShareCart::history';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_ShareCart::history');
        $resultPage->getConfig()->getTitle()->prepend(__('Share Cart History'));

        return $resultPage;
    }
}
