<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model\Config\Source;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Magento\Framework\Data\OptionSourceInterface;

class SharedByType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => ShareCartInterface::SHARED_BY_TYPE_CUSTOMER, 'label' => __('Customer')],
            ['value' => ShareCartInterface::SHARED_BY_TYPE_ADMIN, 'label' => __('Admin')],
            ['value' => ShareCartInterface::SHARED_BY_TYPE_GUEST, 'label' => __('Guest')],
        ];
    }
}
