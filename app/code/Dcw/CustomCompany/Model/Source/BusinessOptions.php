<?php
namespace Dcw\CustomCompany\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BusinessOptions implements OptionSourceInterface
{
    
     /**
      * Return Option.
      *
      * @return Array[]
      */
    public function toOptionArray()
    {
        return [
            
            ['value' => '', 'label' => __('Please select an item')],
            ['value' => 1, 'label' => __('New Construction')],
            ['value' => 2, 'label' => __('Commercial Builder')],
			['value' => 3, 'label' => __('Residential Remodel')],
            ['value' => 4, 'label' => __('General Contractor')],
            ['value' => 5, 'label' => __('Reseller (Flooring Retailer)')],
            ['value' => 6, 'label' => __('Other')],
            // Add more options as needed
        ];
    }
    
    /**
     * Option key and value.
     *
     * @return Array[]
     */
    public function optionvalueinkey()
    {
        $alloption=$this->toOptionArray();
        $valuesinkey=[];
        foreach ($alloption as $option) {
            if ($option['value']!='') {
                $valuesinkey[$option['value']]=$option['label']->__toString();
            }
        }
        return $valuesinkey;
    }
}
