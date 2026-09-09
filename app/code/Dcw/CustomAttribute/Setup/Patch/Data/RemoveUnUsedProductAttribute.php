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
declare(strict_types=1);

namespace Dcw\CustomAttribute\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class RemoveUnUsedProductAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger = $logger;
    }
    /**
     * @return void
     */
    public function apply()
    {
		$attributecode = ['tile_size_area','brand','pdp_feature_6','margin_percentage','is_featured','is_newarrival','deal_of_the_month','free_shipping','plp_feature_1','plp_feature_2','plp_feature_3','plp_feature_4','plp_feature_5','plp_feature_6','antimicrobial','application_location','approximate_tile_size','cases_per_pallet','commercial_residential','flooring_look','flooring_product_type','indoor_outdoor','installs_over_subfloor','max_roll_length_restriction','product_thickness','product_thickness','product_unit_display','ship_next_day','product_image_1','product_image_2','product_image_3','product_image_4','product_image_5','product_image_6','product_image_7','product_image_8','product_image_9','product_image_10','product_image_media_1','product_image_media_2','product_image_media_3','product_image_media_4','product_image_media_5','product_image_media_6','product_image_media_7','product_image_media_8','product_image_media_9','product_image_media_10'];
        try {
			foreach($attributecode as $code){
				/** @var EavSetup $eavSetup */
				$eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
				if($eavSetup->getAttributeId(Product::ENTITY, $code)) {
                    $eavSetup->removeAttribute(Product::ENTITY, $code);
                    }
    
			}
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
    /**
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }
    /**
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}