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
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as eavAttribute;

class AddFeaturesAttribute implements DataPatchInterface, PatchRevertableInterface
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

    const GROUPCODE = "General";

    const ATTRIBUTE_SET_ID = "Default";

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
     * If code fails, patch must be reverted, in case when we are speaking about schema - than under revert
     * means run PatchInterface::revert()
     *
     * If we speak about data, under revert means: $transaction->rollback()
     *
     * @return $this
     */
    public function apply()
    {
        
        $this->moduleDataSetup->startSetup();
        $attribute_code = 'features';
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
                    'label' => 'Features',
                    'input' => 'select',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group' => 'General',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    //'apply_to' => $productTypes,
                   
                ]
            );
            
            $this->eavConfig->clear();

            $this->convertAttributeToSwatches($attribute_code);
            $eavSetup->addAttributeToGroup(Product::ENTITY, self::ATTRIBUTE_SET_ID, self::GROUPCODE, $attribute_code, 209);
        }
        $this->moduleDataSetup->endSetup();
    }
    
    
    public function convertAttributeToSwatches($attribute_code) {
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
        //$attributeData['swatchtext'] = $this->getOptionSwatchText($attributeData);
        $attribute->addData($attributeData);
        $attribute->save();
    }

    protected function getOptionSwatch(array $attributeData)
    {

        $optionSwatch = ['order' => [], 'value' => [], 'delete' => []];
        $i = 0;
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['delete'][$optionKey] = '';
            $optionSwatch['order'][$optionKey] = (string)$i++;
            $optionSwatch['value']['option_' . $optionKey] = [$optionValue,''];
        }

        return $optionSwatch;
    }

    /**
     * @param array $attributeData
     * @return array
     */
    /*private function getOptionSwatchText(array $attributeData)
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['value'][$optionKey] = [$optionValue, ''];
        }
        return $optionSwatch;
    }*/

    /**
     * @param array $attributeData
     * @return array
     */
    /*private function getOptionDefaultText(array $attributeData)
    {
        $optionSwatch = $this->getOptionSwatchText($attributeData);
        return [array_keys($optionSwatch['value'])[0]];
    }*/

    /**
     * @param $attributeId
     * @return void
     */
    private function loadOptionCollection($attributeId)
    {

        if (empty($this->optionCollection[$attributeId])) {
            $this->optionCollection[$attributeId] = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setPositionOrder('asc', true)
                ->load();
        }
    }

    /**
     * @param eavAttribute $attribute
     * @return array
     */
    private function addExistingOptions(eavAttribute $attribute)
    {
        $options = [0=>'Free Shipping',
                    1=>'Made in the USA',
                    2=> 'Attached Pad',
                    3=> 'Stain Resistant',
                    4=> 'Class 1 Fire Rating',
                    5=> 'CRI Green Label Plus'
                ];
        
        return $options;
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

    /**
     * Rollback all changes, done by this patch
     *
     * @return void
     */
    public function revert()
    {
        return;
    }

}
