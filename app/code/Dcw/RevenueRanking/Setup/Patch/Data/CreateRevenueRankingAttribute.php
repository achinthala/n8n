<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;

class CreateRevenueRankingAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        
        $attribute_code = 'revenue_ranking';
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        
        // Check if attribute already exists
        $attribute = $eavSetup->getAttribute(Product::ENTITY, $attribute_code);
        if (!$attribute || !isset($attribute['attribute_id'])) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $attribute_code,
                [
                    'type' => 'text', // Same as sort_by_bestseller
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Revenue Ranking',
                    'input' => 'text', // Same as sort_by_bestseller
                    'class' => '',
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '0', // Default value as requested
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true, // Same as sort_by_bestseller
                    'used_for_sort_by' => true, // This is the key! Same as sort_by_bestseller
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
            
            // Just create the attribute - assignment will be handled by separate patch
        }
        
        $this->moduleDataSetup->endSetup();
        
        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
