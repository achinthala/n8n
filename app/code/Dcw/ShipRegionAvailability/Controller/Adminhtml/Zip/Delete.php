<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Zip;

use Dcw\ShipRegionAvailability\Api\ZipRepositoryInterface;
use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends AbstractAdminAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::zip';

    public function __construct(
        Context $context,
        private readonly ZipRepositoryInterface $zipRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $zipId = (int) $this->getRequest()->getParam('zip_id');
        if ($zipId) {
            try {
                $this->zipRepository->deleteById($zipId);
                $this->messageManager->addSuccessMessage(__('You deleted the ZIP mapping.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t delete this ZIP mapping right now.'));
            }
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
