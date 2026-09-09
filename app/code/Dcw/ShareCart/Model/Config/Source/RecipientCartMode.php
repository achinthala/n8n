<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model\Config\Source;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Magento\Framework\Data\OptionSourceInterface;

class RecipientCartMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => ShareCartInterface::RECIPIENT_CART_MODE_MERGE,
                'label' => __('Merge Cart'),
            ],
            [
                'value' => ShareCartInterface::RECIPIENT_CART_MODE_REPLACE,
                'label' => __('Replace Cart'),
            ],
        ];
    }
}
