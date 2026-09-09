<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Model\Source\Edit;

use Magento\Framework\Data\OptionSourceInterface;

class AllowQuoteEdit implements OptionSourceInterface
{
    public const NO = 0;
    public const PENDING = 1;
    public const APPROVED = 2;
    public const PENDING_AND_APPROVED = 3;

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::NO,
                'label' => __('No')
            ],
            [
                'value' => self::PENDING,
                'label' => __('Pending')
            ],
            [
                'value' => self::APPROVED,
                'label' => __('Approved')
            ],
            [
                'value' => self::PENDING_AND_APPROVED,
                'label' => __('Pending and Approved')
            ]
        ];
    }
}

