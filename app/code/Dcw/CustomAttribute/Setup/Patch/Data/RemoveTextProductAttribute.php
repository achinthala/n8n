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

class RemoveTextProductAttribute implements DataPatchInterface
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
		$attributecode = ['coverage_per_case','overage_percent','product_unit','pdp_shipping_text','product_notification_bar1','product_notification_bar2','product_marketing_banner','pdp_feature_1','pdp_feature_2','pdp_feature_3','pdp_feature_4','pdp_feature_5'];
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