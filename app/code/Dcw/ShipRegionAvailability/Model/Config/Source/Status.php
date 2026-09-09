<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\Config\Source;

use Dcw\ShipRegionAvailability\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::STATUS_ENABLED, 'label' => __('Enabled')],
            ['value' => Config::STATUS_DISABLED, 'label' => __('Disabled')],
        ];
    }
}
