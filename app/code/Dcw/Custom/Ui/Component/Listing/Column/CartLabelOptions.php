<?php

namespace Dcw\Custom\Ui\Component\Listing\Column;

use Dcw\Custom\Helper\Data as CustomHelperData;
use Magento\Framework\Data\OptionSourceInterface;

class CartLabelOptions implements OptionSourceInterface
{
    protected $customHelperData;
    
    public function __construct(
        CustomHelperData $customHelperData
    ) {
        $this->customHelperData = $customHelperData;
    }

    public function toOptionArray()
    {
        return $this->customHelperData->getCartLabelOptions();
    }
}
