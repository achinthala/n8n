<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Zip;

use Dcw\ShipRegionAvailability\Api\ZipRepositoryInterface;
use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Dcw\ShipRegionAvailability\Model\ZipFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends AbstractAdminAction implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::zip';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ZipFactory $zipFactory,
        private readonly ZipRepositoryInterface $zipRepository,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $zipId = (int) $this->getRequest()->getParam('zip_id');
        if ($zipId) {
            try {
                $zip = $this->zipRepository->getById($zipId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This ZIP mapping no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } else {
            $zip = $this->zipFactory->create();
        }

        $this->coreRegistry->register('dcw_ship_region_zip', $zip);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_ShipRegionAvailability::zip');
        $resultPage->getConfig()->getTitle()->prepend(
            $zipId ? __('Edit ZIP Mapping') : __('New ZIP Mapping')
        );
        return $resultPage;
    }
}
