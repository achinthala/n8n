<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Controller\Account;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class History extends AbstractAccount implements HttpGetActionInterface
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Shared Cart History'));

        return $resultPage;
    }
}
