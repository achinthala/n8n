<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Zip;

use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Dcw\ShipRegionAvailability\Model\Export\ZipCsvExporter;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;

class Export extends AbstractAdminAction implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::zip';

    public function __construct(
        Context $context,
        private readonly FileFactory $fileFactory,
        private readonly ZipCsvExporter $zipCsvExporter
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            return $this->fileFactory->create(
                'ship_region_zip_export_' . date('Y-m-d_His') . '.csv',
                [
                    'type' => 'string',
                    'value' => $this->zipCsvExporter->getExportCsvContent(),
                ],
                DirectoryList::VAR_DIR,
                'text/csv'
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Error exporting ZIP mappings: %1', $e->getMessage())
            );
            return $this->resultRedirectFactory->create()->setPath('*/*/import');
        }
    }
}
