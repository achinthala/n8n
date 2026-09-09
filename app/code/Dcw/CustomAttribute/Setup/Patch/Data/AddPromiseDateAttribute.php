<?php
/**
 * CustomAttribute
 *
 * PHP version 8
 *
 * @category  PHP
 * @package   Dcw_CustomAttribute
 * @author    Rahul
 * @copyright 2024 Dotcom. All rights reserved.
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0
 * @link      https://dotcomweavers.com/
 */
namespace Dcw\CustomAttribute\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as eavAttribute;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddPromiseDateAttribute implements DataPatchInterface
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
        EavConfig $eavConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Run code inside patch
     *
     * @return $this
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $attributeCode = 'promise_date';
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
        
        if (!$attribute || !$attribute->getAttributeId()) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $attributeCode,
                [
                    'type' => 'datetime',
                    'label' => 'Promise Date',
                    'input' => 'date',
                    'backend' => '',
                    'frontend' => '',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false,
                ]
            );

            $eavSetup->addAttributeToGroup(
                Product::ENTITY,
                self::ATTRIBUTE_SET_ID,
                self::GROUPCODE,
                $attributeCode,
                230
            );

            $this->eavConfig->clear();
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Get array of patches that have to be executed prior to this.
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
