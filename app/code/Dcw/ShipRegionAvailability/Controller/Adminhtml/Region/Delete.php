<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Region;

use Dcw\ShipRegionAvailability\Api\RegionRepositoryInterface;
use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends AbstractAdminAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::region';

    public function __construct(
        Context $context,
        private readonly RegionRepositoryInterface $regionRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $regionId = (int) $this->getRequest()->getParam('region_id');
        if ($regionId) {
            try {
                $this->regionRepository->deleteById($regionId);
                $this->messageManager->addSuccessMessage(__('You deleted the region.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t delete this region right now.'));
            }
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
