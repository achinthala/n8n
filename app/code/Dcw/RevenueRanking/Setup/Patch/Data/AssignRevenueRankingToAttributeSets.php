<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Entity\Type;

class AssignRevenueRankingToAttributeSets implements DataPatchInterface
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
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;
    
    /**
     * @var Type
     */
    private $entityType;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        Type $entityType
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->entityType = $entityType;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        
        try {
            // Get the entity type ID for products
            $entityType = $this->entityType->loadByCode(Product::ENTITY);
            $entityTypeId = $entityType->getId();
            
            // Get all attribute sets for products
            $attributeSetCollection = $this->attributeSetFactory->create()
                ->getCollection()
                ->setEntityTypeFilter($entityTypeId);
            
            $this->moduleDataSetup->getConnection()->beginTransaction();
            
            foreach ($attributeSetCollection as $attributeSet) {
                try {
                    // Add to General group with position 209
                    $eavSetup->addAttributeToGroup(
                        Product::ENTITY,
                        $attributeSet->getAttributeSetName(),
                        'General',
                        'revenue_ranking',
                        209 // Position after sort_by_bestseller
                    );
                    
                } catch (\Exception $e) {
                    // If adding to a specific attribute set fails, continue with others
                    continue;
                }
            }
            
            $this->moduleDataSetup->getConnection()->commit();
            
        } catch (\Exception $e) {
            $this->moduleDataSetup->getConnection()->rollBack();
            
            // Fallback: Just add to Default attribute set
            try {
                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    'Default',
                    'General',
                    'revenue_ranking',
                    209
                );
            } catch (\Exception $fallbackException) {
                // If even fallback fails, just continue
            }
        }
        
        $this->moduleDataSetup->endSetup();
        
        return $this;
    }

    public static function getDependencies()
    {
        return [
            CreateRevenueRankingAttribute::class
        ];
    }

    public function getAliases()
    {
        return [];
    }
}
