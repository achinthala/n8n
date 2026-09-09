<?php

namespace Dcw\CustomAttribute\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Category;

class AddAkeneoIdCategoryAttribute implements DataPatchInterface
{
protected $eavSetupFactory;
protected $moduleDataSetup;

public function __construct(
EavSetupFactory $eavSetupFactory,
ModuleDataSetupInterface $moduleDataSetup
) {
$this->eavSetupFactory = $eavSetupFactory;
$this->moduleDataSetup = $moduleDataSetup;
}

public function apply()
{
/** @var EavSetup $eavSetup */
$eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

$eavSetup->addAttribute(
Category::ENTITY,
'akeneo_id',
[
'type' => 'varchar',
'label' => 'Akeneo Id',
'input' => 'text',
'required' => true,
'unique' => true,
'sort_order' => 10,
'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
'group' => 'General Information',
]
);
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