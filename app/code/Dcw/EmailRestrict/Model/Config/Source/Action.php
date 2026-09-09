<?php
namespace Dcw\EmailRestrict\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Action implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'block', 'label' => __('Block (do not send)')],
            ['value' => 'strip', 'label' => __('Strip non-allowed recipients')]
        ];
    }
}
