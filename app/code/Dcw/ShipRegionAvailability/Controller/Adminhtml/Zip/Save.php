<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Zip;

use Dcw\ShipRegionAvailability\Api\Data\ZipInterface;
use Dcw\ShipRegionAvailability\Api\RegionLookupInterface;
use Dcw\ShipRegionAvailability\Api\ZipRepositoryInterface;
use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\ZipFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends AbstractAdminAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::zip';

    public function __construct(
        Context $context,
        private readonly ZipFactory $zipFactory,
        private readonly ZipRepositoryInterface $zipRepository,
        private readonly RegionLookupInterface $regionLookup,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        /** @var array<string, mixed> $data */
        $data = is_array($post['data'] ?? null) ? $post['data'] : $post;

        $zipId = (int) ($data[ZipInterface::ZIP_ID] ?? 0);
        $zip = $this->zipFactory->create();
        if ($zipId) {
            try {
                $zip = $this->zipRepository->getById($zipId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This ZIP mapping no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $normalized = $this->regionLookup->normalizeZipCode((string) ($data[ZipInterface::ZIP_CODE] ?? ''));
        $regionId = (int) ($data[ZipInterface::REGION_ID] ?? 0);

        if ($normalized === null || $regionId <= 0) {
            $this->messageManager->addErrorMessage(__('A valid 5-digit ZIP and region are required.'));
            $this->dataPersistor->set('dcw_ship_region_zip', $data);
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['zip_id' => $zipId]);
        }

        $zip->setZipCode($normalized);
        $zip->setRegionId($regionId);
        $zip->setStatus((int) ($data[ZipInterface::STATUS] ?? Config::STATUS_ENABLED));

        try {
            $this->zipRepository->save($zip);
            $this->messageManager->addSuccessMessage(__('You saved the ZIP mapping.'));
            $this->dataPersistor->clear('dcw_ship_region_zip');

            $redirect = $this->resultRedirectFactory->create();
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['zip_id' => (int) $zip->getZipId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the ZIP mapping.'));
        }

        $this->dataPersistor->set('dcw_ship_region_zip', $data);
        return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['zip_id' => $zipId]);
    }
}
