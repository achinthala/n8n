<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LinkExpiryDays implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('1 Day')],
            ['value' => '3', 'label' => __('3 Days')],
            ['value' => '7', 'label' => __('7 Days')],
            ['value' => '14', 'label' => __('14 Days')],
            ['value' => '30', 'label' => __('30 Days')],
            ['value' => '60', 'label' => __('60 Days')],
            ['value' => '90', 'label' => __('90 Days')],
        ];
    }
}
