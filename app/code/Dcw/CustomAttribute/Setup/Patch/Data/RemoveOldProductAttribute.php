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

class RemoveOldProductAttribute implements DataPatchInterface
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
		$attributecode = ['product_unit_display','product_notification_bar3','product_notification_bar4','designer_tools','tiles_size','pdp_icon_1','pdp_icon_2','pdp_icon_3','pdp_icon_4','pdp_icon_5','pdp_icon_6','product_label','min_expected_days_ship','max_expected_days_ship','color','color_finish','edge_type','gloss','installation','installation_method','maintenance','location','material','wear_layer_thickness','min_tile_quantity','product_length','product_width','free_tshirt'];
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