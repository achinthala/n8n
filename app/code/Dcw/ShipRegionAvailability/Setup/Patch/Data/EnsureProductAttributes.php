<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Setup\Patch\Data;

use Dcw\ShipRegionAvailability\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Creates product attributes only when they do not already exist (PIM may have created them).
 */
class EnsureProductAttributes implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly EavConfig $eavConfig
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->ensureShipsFromRegionAttribute($eavSetup);
        $this->ensureParentAvailabilityFlag($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    private function ensureShipsFromRegionAttribute(EavSetup $eavSetup): void
    {
        $code = Config::ATTR_SHIPS_FROM_REGION;
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
        if ($attribute && $attribute->getId()) {
            return;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            [
                'type' => 'varchar',
                'label' => 'Ships From Region',
                'input' => 'multiselect',
                'backend' => ArrayBackend::class,
                'source' => Table::class,
                'required' => false,
                'user_defined' => true,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'apply_to' => 'simple',
                'group' => 'General',
                'note' => 'Multiselect options and values are managed via PIM/API sync (EAV attribute options).',
            ]
        );
    }

    private function ensureParentAvailabilityFlag(EavSetup $eavSetup): void
    {
        $code = Config::ATTR_SHIP_REGION_AVAILABILITY;
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
        if ($attribute && $attribute->getId()) {
            return;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            $code,
            [
                'type' => 'int',
                'label' => 'Ship Region Availability Enabled',
                'input' => 'boolean',
                'source' => Boolean::class,
                'required' => false,
                'user_defined' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'default' => '0',
                'apply_to' => 'configurable',
                'group' => 'General',
                'note' => 'When Yes, PDP shows ZIP-based region shipping color grouping.',
            ]
        );
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
