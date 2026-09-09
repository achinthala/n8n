<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Region;

use Dcw\ShipRegionAvailability\Api\Data\RegionInterface;
use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\RegionFactory;
use Dcw\ShipRegionAvailability\Api\RegionRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends AbstractAdminAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::region';

    public function __construct(
        Context $context,
        private readonly RegionFactory $regionFactory,
        private readonly RegionRepositoryInterface $regionRepository,
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

        $regionId = (int) ($data[RegionInterface::REGION_ID] ?? 0);
        $region = $this->regionFactory->create();
        if ($regionId) {
            try {
                $region = $this->regionRepository->getById($regionId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This region no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $code = strtolower(trim((string) ($data[RegionInterface::CODE] ?? '')));
        $name = trim((string) ($data[RegionInterface::NAME] ?? ''));

        if ($code === '' || $name === '') {
            $this->messageManager->addErrorMessage(__('Region code and name are required.'));
            $this->dataPersistor->set('dcw_ship_region', $data);
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['region_id' => $regionId]);
        }

        $region->setCode($code);
        $region->setName($name);
        $region->setStatus((int) ($data[RegionInterface::STATUS] ?? Config::STATUS_ENABLED));
        $region->setSortOrder((int) ($data[RegionInterface::SORT_ORDER] ?? 0));

        try {
            $this->regionRepository->save($region);
            $this->messageManager->addSuccessMessage(__('You saved the region.'));
            $this->dataPersistor->clear('dcw_ship_region');

            $redirect = $this->resultRedirectFactory->create();
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['region_id' => (int) $region->getRegionId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the region.'));
        }

        $this->dataPersistor->set('dcw_ship_region', $data);
        return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['region_id' => $regionId]);
    }
}
