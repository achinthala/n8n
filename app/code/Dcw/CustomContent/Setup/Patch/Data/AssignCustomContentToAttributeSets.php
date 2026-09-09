<?php
declare(strict_types=1);

namespace Dcw\CustomContent\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AssignCustomContentToAttributeSets implements DataPatchInterface
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

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        try {
            $entityType = $this->entityType->loadByCode(Product::ENTITY);
            $entityTypeId = $entityType->getId();

            $attributeSetCollection = $this->attributeSetFactory->create()
                ->getCollection()
                ->setEntityTypeFilter($entityTypeId);

            $this->moduleDataSetup->getConnection()->beginTransaction();

            foreach ($attributeSetCollection as $attributeSet) {
                $setName = $attributeSet->getAttributeSetName();
                try {
                    $eavSetup->addAttributeToGroup(
                        Product::ENTITY,
                        $setName,
                        'General',
                        'custom_content',
                        300
                    );
                } catch (\Exception $e) {
                    // continue with next
                }
                try {
                    $eavSetup->addAttributeToGroup(
                        Product::ENTITY,
                        $setName,
                        'General',
                        'pdp_custom_content',
                        310
                    );
                } catch (\Exception $e) {
                    // continue with next
                }
            }

            $this->moduleDataSetup->getConnection()->commit();
        } catch (\Exception $e) {
            $this->moduleDataSetup->getConnection()->rollBack();
            try {
                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    'Default',
                    'General',
                    'custom_content',
                    300
                );
                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    'Default',
                    'General',
                    'pdp_custom_content',
                    310
                );
            } catch (\Exception $fallbackException) {
                // ignore
            }
        }

        $this->moduleDataSetup->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            AddCustomContentAttributes::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
