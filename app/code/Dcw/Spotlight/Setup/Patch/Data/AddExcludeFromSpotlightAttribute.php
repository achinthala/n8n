<?php
/**
 * AddExcludeFromSpotlightAttribute
 *
 * Adds a boolean category attribute to exclude categories from Spotlight URL generation.
 * This allows categories like "Specials", "Clearance", "Samples", etc. to be excluded
 * from being used as landing pages in the Spotlight feed.
 *
 * @category  Dcw
 * @package   Dcw_Spotlight
 * @author    Dotcomweavers
 * @copyright 2025 Dotcomweavers. All rights reserved.
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0
 * @link      https://dotcomweavers.com/
 */
namespace Dcw\Spotlight\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config as EavConfig;

class AddExcludeFromSpotlightAttribute implements DataPatchInterface
{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * EavSetupFactory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     * @param EavConfig                $eavConfig
     */
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
     * Add exclude_from_spotlight category attribute
     *
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attribute = $this->eavConfig->getAttribute(Category::ENTITY, 'exclude_from_spotlight');
        
        if (!$attribute || !$attribute->getAttributeId()) {
            $eavSetup->addAttribute(Category::ENTITY, 'exclude_from_spotlight', [
                'type' => 'int',
                'label' => 'Exclude from Spotlight URLs',
                'input' => 'select',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'required' => false,
                'sort_order' => 335,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'note' => 'If set to Yes, products in this category will not use this category for Spotlight landing URLs. Useful for temporary categories like Specials, Clearance, or Samples.'
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}

