<?php

declare(strict_types=1);

namespace Dcw\Feed\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SfPricingMode implements OptionSourceInterface
{
    public const MODE_DISABLED = 'disabled';

    public const MODE_WHITELIST = 'whitelist';

    public const MODE_ALL = 'all';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MODE_DISABLED, 'label' => __('Disabled (original prices)')],
            ['value' => self::MODE_WHITELIST, 'label' => __('Whitelisted parent SKUs only')],
            ['value' => self::MODE_ALL, 'label' => __('All products')],
        ];
    }
}
