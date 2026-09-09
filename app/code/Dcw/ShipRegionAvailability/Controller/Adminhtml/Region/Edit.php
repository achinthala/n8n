<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Region;

use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Dcw\ShipRegionAvailability\Api\RegionRepositoryInterface;
use Dcw\ShipRegionAvailability\Model\RegionFactory;

class Edit extends AbstractAdminAction implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::region';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RegionFactory $regionFactory,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $regionId = (int) $this->getRequest()->getParam('region_id');
        if ($regionId) {
            try {
                $region = $this->regionRepository->getById($regionId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This region no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } else {
            $region = $this->regionFactory->create();
        }

        $this->coreRegistry->register('dcw_ship_region', $region);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_ShipRegionAvailability::region');
        $resultPage->getConfig()->getTitle()->prepend(
            $regionId ? __('Edit Region') : __('New Region')
        );
        return $resultPage;
    }
}
