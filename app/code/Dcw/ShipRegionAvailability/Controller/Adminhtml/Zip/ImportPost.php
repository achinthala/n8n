<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml\Zip;

use Dcw\ShipRegionAvailability\Controller\Adminhtml\AbstractAdminAction;
use Dcw\ShipRegionAvailability\Model\Config\Source\ZipImportMode;
use Dcw\ShipRegionAvailability\Model\Import\ZipCsvImporter;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class ImportPost extends AbstractAdminAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::zip';

    public function __construct(
        Context $context,
        private readonly ZipCsvImporter $zipCsvImporter
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $file = $this->getRequest()->getFiles('import_file');
        $mode = (string) $this->getRequest()->getParam(
            'import_mode',
            ZipImportMode::MODE_MERGE
        );

        try {
            $result = $this->zipCsvImporter->import($file, $mode);

            if ($result['mode'] === ZipImportMode::MODE_REPLACE_ALL) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Replace import finished. %1 existing ZIP mapping(s) removed. %2 row(s) imported, %3 skipped.',
                        $result['deleted'],
                        $result['imported'],
                        $result['skipped']
                    )
                );
            } else {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Merge import finished. %1 row(s) imported or updated, %2 skipped.',
                        $result['imported'],
                        $result['skipped']
                    )
                );
            }
            if (!empty($result['errors'])) {
                $this->messageManager->addWarningMessage(
                    __('Some rows had errors: %1', implode(' | ', array_slice($result['errors'], 0, 10)))
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('ZIP import failed.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/import');
    }
}
