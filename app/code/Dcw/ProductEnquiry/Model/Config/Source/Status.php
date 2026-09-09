<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CLOSED = 'closed';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::STATUS_NEW, 'label' => __('New')],
            ['value' => self::STATUS_IN_PROGRESS, 'label' => __('In Progress')],
            ['value' => self::STATUS_CLOSED, 'label' => __('Closed')],
        ];
    }
}
