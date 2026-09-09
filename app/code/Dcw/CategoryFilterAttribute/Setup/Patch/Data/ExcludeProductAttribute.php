<?php

declare(strict_types=1);

namespace Dcw\CategoryFilterAttribute\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ExcludeProductAttribute implements DataPatchInterface
{
    /**
     * Attribute code
     */
    private const ATTRIBUTE_CODE = 'exclude_product_attribute';

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var CategorySetupFactory
     */
    private CategorySetupFactory $categorySetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * @return array
     */
    public static function getDependencies() : array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases() : array
    {
        return [];
    }

    /**
     * @return $this
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply() : ExcludeProductAttribute
    {
        $eavSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Category::ENTITY,
            self::ATTRIBUTE_CODE,
            [
                'type' => 'text',
                'label' => 'Exclude attribute from layered navigation',
                'input' => 'textarea',
                'required' => false,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'sort_order' => 31,
                'group' => 'General',
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_user_defined' => true,
                'is_visible' => true,
            ]
        );
        return $this;
    }
}
