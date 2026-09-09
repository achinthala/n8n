<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class WeightClass implements OptionSourceInterface
{
    /**
     * Get weight class options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'CLASS_50', 'label' => __('Class 50')],
            ['value' => 'CLASS_55', 'label' => __('Class 55')],
            ['value' => 'CLASS_60', 'label' => __('Class 60')],
            ['value' => 'CLASS_65', 'label' => __('Class 65')],
            ['value' => 'CLASS_70', 'label' => __('Class 70')],
            ['value' => 'CLASS_77_5', 'label' => __('Class 77.5')],
            ['value' => 'CLASS_85', 'label' => __('Class 85')],
            ['value' => 'CLASS_92_5', 'label' => __('Class 92.5')],
            ['value' => 'CLASS_100', 'label' => __('Class 100')],
            ['value' => 'CLASS_110', 'label' => __('Class 110')],
            ['value' => 'CLASS_125', 'label' => __('Class 125')],
            ['value' => 'CLASS_150', 'label' => __('Class 150')],
            ['value' => 'CLASS_175', 'label' => __('Class 175')],
            ['value' => 'CLASS_200', 'label' => __('Class 200')],
            ['value' => 'CLASS_250', 'label' => __('Class 250')],
            ['value' => 'CLASS_300', 'label' => __('Class 300')],
            ['value' => 'CLASS_400', 'label' => __('Class 400')],
            ['value' => 'CLASS_500', 'label' => __('Class 500')]
        ];
    }
}