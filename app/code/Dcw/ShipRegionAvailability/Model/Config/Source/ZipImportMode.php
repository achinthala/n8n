<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ZipImportMode implements OptionSourceInterface
{
    public const MODE_MERGE = 'merge';
    public const MODE_REPLACE_ALL = 'replace_all';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::MODE_MERGE,
                'label' => __('Merge only (update existing ZIPs, add new; keep others)'),
            ],
            [
                'value' => self::MODE_REPLACE_ALL,
                'label' => __('Replace all (delete all ZIP mappings, then import file)'),
            ],
        ];
    }
}
