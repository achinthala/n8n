<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Region;

use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends AbstractAdminAction implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::region';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_ShipRegionAvailability::region');
        $resultPage->getConfig()->getTitle()->prepend(__('Ship Regions'));
        return $resultPage;
    }
}
