<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model\Config\Source;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Magento\Framework\Data\OptionSourceInterface;

class LinkStatus implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => ShareCartInterface::LINK_STATUS_ACTIVE, 'label' => __('Active')],
            ['value' => ShareCartInterface::LINK_STATUS_EXPIRED, 'label' => __('Expired')],
            ['value' => ShareCartInterface::LINK_STATUS_USED, 'label' => __('Used')],
            ['value' => ShareCartInterface::LINK_STATUS_REVOKED, 'label' => __('Revoked')],
        ];
    }
}
