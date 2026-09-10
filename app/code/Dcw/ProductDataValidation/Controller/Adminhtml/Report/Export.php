<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Controller\Adminhtml\Report;

use Dcw\ProductDataValidation\Model\ResourceModel\Result\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Store\Model\StoreManagerInterface;

/**
 * CSV export of validation results (optionally scoped to a job_id).
 */
class Export extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ProductDataValidation::export';

    public function __construct(
        Context $context,
        private readonly FileFactory $fileFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $collection = $this->collectionFactory->create();
            $jobId = (int) $this->getRequest()->getParam('job_id');
            if ($jobId > 0) {
                $collection->addFieldToFilter('job_id', $jobId);
            }
            $sku = trim((string) $this->getRequest()->getParam('sku'));
            if ($sku !== '') {
                $collection->addFieldToFilter('sku', ['like' => '%' . $sku . '%']);
            }
            $collection->setOrder('result_id', 'ASC');

            $csvData = [];
            $csvData[] = ['SKU', 'Product Name', 'Store View', 'Missing Attributes', 'Status', 'Job ID', 'Checked At'];

            $storeNames = [];
            foreach ($collection as $item) {
                $storeId = (int) $item->getData('store_id');
                if (!isset($storeNames[$storeId])) {
                    try {
                        $storeNames[$storeId] = $this->storeManager->getStore($storeId)->getName();
                    } catch (\Throwable) {
                        $storeNames[$storeId] = (string) $storeId;
                    }
                }
                $csvData[] = [
                    $item->getData('sku'),
                    $item->getData('product_name'),
                    $storeNames[$storeId],
                    $item->getData('missing_attributes'),
                    $item->getData('status'),
                    $item->getData('job_id'),
                    $item->getData('created_at'),
                ];
            }

            $fileName = 'product_data_validation_' . date('Y-m-d_H-i-s') . '.csv';

            return $this->fileFactory->create(
                $fileName,
                $csvData,
                DirectoryList::VAR_DIR,
                'text/csv'
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while exporting the report.')
            );
            /** @var Redirect $redirect */
            $redirect = $this->resultRedirectFactory->create();
            return $redirect->setPath('product_data_validation/report/index');
        }
    }
}
