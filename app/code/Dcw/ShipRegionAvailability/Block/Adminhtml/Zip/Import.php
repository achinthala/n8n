<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Block\Adminhtml\Zip;

use Dcw\ShipRegionAvailability\Model\Config\Source\ZipImportMode;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Import extends Template
{
    public function __construct(
        Context $context,
        private readonly ZipImportMode $zipImportMode,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function getImportModeOptions(): array
    {
        return $this->zipImportMode->toOptionArray();
    }

    public function getDefaultImportMode(): string
    {
        return ZipImportMode::MODE_MERGE;
    }

    public function getPostUrl(): string
    {
        return $this->getUrl('dcw_shipregion/zip/importPost');
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('dcw_shipregion/zip/index');
    }

    public function getSampleCsvUrl(): string
    {
        return $this->getUrl('dcw_shipregion/zip/downloadSample');
    }

    public function getExportCsvUrl(): string
    {
        return $this->getUrl('dcw_shipregion/zip/export');
    }
}
