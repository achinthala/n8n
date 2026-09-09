<?php

declare(strict_types=1);

namespace Dcw\CategoryFilterAttribute\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class ExcludeProductAttribute implements ArgumentInterface
{
    /**
     * Convert into an array the settings from the category page for the field exclude_product_attribute
     * @param string $excludeProductAttribute
     * @return array
     */
    private function convertIntoArray(string $excludeProductAttribute) : array
    {
        $attributesToArray = explode('|', $excludeProductAttribute);
        $results = [];
        foreach ($attributesToArray as $attribute) {
            // search values between ()
            if (preg_match('/^(.*?)\((.*?)\)$/', trim($attribute), $matches)) {
                $key = $matches[1];   // attribute name
                $value = $matches[2]; // attribute values
                $results[$key] = explode(',', $value);
            }
        }
        return $results;
    }

    /**
     * Check if the label of the attribute should show up on the layered navigation.
     * @param string $label
     * @param string $attributeCode
     * @param string $excludeProductAttribute
     * @return bool
     */
    public function shouldShowAttributeFilter(string $label, string $attributeCode, string $excludeProductAttribute) : bool
    {
        $attributesToArray = $this->convertIntoArray($excludeProductAttribute);
        foreach ($attributesToArray as $attributeId => $values) {
            if ($attributeId == $attributeCode) {
                foreach ($values as $value) {
                    if (strtolower(trim($label)) == strtolower(trim($value))) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
