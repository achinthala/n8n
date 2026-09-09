<?php
/**
 * PriceCalculation
 *
 * PHP version 7
 *
 * @category  PHP
 * @package   Dw_PriceCalculation
 * @author    Rahul
 * @copyright 2021 Dotcom. All rights reserved.
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0
 * @link      https://dotcomweavers.com/
 */
namespace Dcw\CustomAttribute\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as eavAttribute;

class AddProductOptionAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    private const GROUPCODE = "General";

    private const ATTRIBUTE_SET_ID = "Default";

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        EavConfig $eavConfig,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
    }

    /**
     * Run code inside patch
     *
     * @return $this
     */
    public function apply()
    {
        
        $this->moduleDataSetup->startSetup();
        $attribute_code = 'product_option';
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attribute_code);
        if (!$attribute || !$attribute->getAttributeId()) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $attribute_code,
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Product Option',
                    'input' => 'select',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => 0,
                    'searchable' => false,
                    'filterable' => true,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'option' => [
                        'values' => [
                            0=>'1/2" thick - Big Chip™ Colors',
                            1=>'1/2" thick - Biggie Smallz™ Colors',
                            2=> '1/2" thick - Standard Colors',
                            3=> "2' x 2' Tiles",
                            4=> '3/8" thick - Big Chip™ Colors',
                            5=> '3/8" thick - Biggie Smallz™ Colors',
                            6=> '3/8" thick - Standard Colors',
                            7=> "4' x 4' Tiles",
                            8=> "4' x 6' Tiles",
                            9=> '2.5" Tile Qty. 125+',
                            10=> '2.5" Tile Qty. 1 - 124',
                            11=> '9" x 36" planks (18 sqft/carton)',
                            12=> '24" x 24" tiles (32 sqft/carton)',
                        ],
                     ]
                ]
            );
            $eavSetup->addAttributeToGroup(
                Product::ENTITY,
                self::ATTRIBUTE_SET_ID,
                self::GROUPCODE,
                $attribute_code,
                230
            );
            $this->eavConfig->clear();
            $this->convertSizeToSwatches($attribute_code);
        }
        $this->moduleDataSetup->endSetup();
    }
    
    public function convertSizeToSwatches($attribute_code)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attribute_code);
        if (!$attribute) {
            return;
        }
        $attributeData['option'] = $this->addExistingOptions($attribute);
        $attributeData['frontend_input'] = 'select';
        $attributeData['swatch_input_type'] = 'text';
        $attributeData['update_product_preview_image'] = 1;
        $attributeData['use_product_image_for_swatch'] = 0;
        $attributeData['optiontext'] = $this->getOptionSwatch($attributeData);
        //$attributeData['defaulttext'] = $this->getOptionDefaultText($attributeData);
        $attributeData['swatchtext'] = $this->getOptionSwatchText($attributeData);
        $attribute->addData($attributeData);
        $attribute->save();
    }
    
    private function loadOptionCollection($attributeId)
    {
        if (empty($this->optionCollection[$attributeId])) {
            $this->optionCollection[$attributeId] = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setPositionOrder('asc', true)
                ->load();
        }
    }
    
    private function addExistingOptions(eavAttribute $attribute)
    {
        $options = [];
        $attributeId = $attribute->getId();
        if ($attributeId) {
            $this->loadOptionCollection($attributeId);
            /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
            foreach ($this->optionCollection[$attributeId] as $option) {
                $options[$option->getId()] = $option->getValue();
            }
        }
        return $options;
    }
    
    protected function getOptionSwatch(array $attributeData)
    {
        $optionSwatch = ['order' => [], 'value' => [], 'delete' => []];
        $i = 0;
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['delete'][$optionKey] = '';
            $optionSwatch['order'][$optionKey] = (string)$i++;
            $optionSwatch['value'][$optionKey] = [$optionValue, ''];
        }
        return $optionSwatch;
    }
    
    private function getOptionSwatchText(array $attributeData)
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['value'][$optionKey] = [$optionValue, ''];
        }
        return $optionSwatch;
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * Example of implementation:
     *
     * [
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch1::class,
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch2::class
     * ]
     *
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
