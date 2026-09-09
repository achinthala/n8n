<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Zip;

use Dcw\ShipRegionAvailability\Api\ZipRepositoryInterface;
use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;

class MassStatus extends AbstractAdminAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::zip';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ZipRepositoryInterface $zipRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $status = (int) $this->getRequest()->getParam('status');
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;
        foreach ($collection as $zip) {
            try {
                $zip->setStatus($status);
                $this->zipRepository->save($zip);
                $updated++;
            } catch (\Exception $e) {
                continue;
            }
        }
        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been updated.', $updated));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
