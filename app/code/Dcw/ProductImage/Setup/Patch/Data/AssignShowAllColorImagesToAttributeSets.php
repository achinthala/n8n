<?php
declare(strict_types=1);

namespace Dcw\ProductImage\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory as AttributeGroupCollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AssignShowAllColorImagesToAttributeSets implements DataPatchInterface
{
    private const GROUP_NAME = 'General';
    private const SORT_ORDER = 320;

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly AttributeSetFactory $attributeSetFactory,
        private readonly Type $entityType,
        private readonly AttributeGroupCollectionFactory $attributeGroupCollectionFactory
    ) {
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
                $assigned = false;
                try {
                    $eavSetup->addAttributeToGroup(
                        Product::ENTITY,
                        $setName,
                        self::GROUP_NAME,
                        AddShowAllColorImagesAttribute::ATTRIBUTE_CODE,
                        self::SORT_ORDER
                    );
                    $assigned = true;
                } catch (\Exception $e) {
                    // General may be missing or attribute already in set
                }
                if (!$assigned) {
                    $groupCollection = $this->attributeGroupCollectionFactory->create();
                    $groupCollection->addFieldToFilter('attribute_set_id', (int) $attributeSet->getId());
                    $groupCollection->setOrder('sort_order', 'ASC');
                    $firstGroup = $groupCollection->getFirstItem();
                    if ($firstGroup->getId()) {
                        try {
                            $eavSetup->addAttributeToGroup(
                                Product::ENTITY,
                                $setName,
                                $firstGroup->getAttributeGroupName(),
                                AddShowAllColorImagesAttribute::ATTRIBUTE_CODE,
                                self::SORT_ORDER
                            );
                        } catch (\Exception $e) {
                            // Already assigned or other; skip
                        }
                    }
                }
            }

            $this->moduleDataSetup->getConnection()->commit();
        } catch (\Exception $e) {
            $this->moduleDataSetup->getConnection()->rollBack();
            try {
                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    'Default',
                    self::GROUP_NAME,
                    AddShowAllColorImagesAttribute::ATTRIBUTE_CODE,
                    self::SORT_ORDER
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
    public static function getDependencies(): array
    {
        return [
            AddShowAllColorImagesAttribute::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
