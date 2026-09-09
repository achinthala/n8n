<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RequestType implements OptionSourceInterface
{
    /**
     * Get request type options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'simple', 'label' => __('Simple')],
            ['value' => 'product', 'label' => __('Product')],
            ['value' => 'quote', 'label' => __('Quote')],
            ['value' => 'order', 'label' => __('Order')]
        ];
    }
}