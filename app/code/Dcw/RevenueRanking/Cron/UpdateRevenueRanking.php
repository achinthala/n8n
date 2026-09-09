<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Cron;

use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking as RevenueRankingResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Logger\Monolog as LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\ActionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Dcw\RevenueRanking\Model\RevenueRankingService;
use Magento\Eav\Model\Config as EavConfig;

class UpdateRevenueRanking
{
    /**
     * @var RevenueRankingResource
     */
    private $revenueRankingResource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ActionFactory
     */
    private $productActionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
	
	/**
     * @var RevenueRankingService
     */
    protected $resourceConnection;
	
	/**
     * @var EavConfig
     */
    protected $eavConfig;

    public function __construct(
        RevenueRankingResource $revenueRankingResource,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ActionFactory $productActionFactory,
        StoreManagerInterface $storeManager,
		RevenueRankingService $resourceConnection,
		EavConfig $eavConfig
    ) {
        $this->revenueRankingResource = $revenueRankingResource;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->productActionFactory = $productActionFactory;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
		$this->eavConfig = $eavConfig;
    }

    /**
     * Execute cron job
     */
    public function execute()
    {
        try {
            // Check if auto update is enabled
            $isEnabled = $this->scopeConfig->getValue(
                'dcw_revenue_ranking/cron_settings/enabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if (!$isEnabled) {
                $this->logger->info('Revenue Ranking: Auto update is disabled, skipping cron job');
				$this->createLogs('Revenue Ranking: Auto update is disabled, skipping cron job top');
                return;
            }

            $this->logger->info('Starting revenue ranking update');
            
            // Get sales period from configuration (default 30 days)
            $salesPeriodDays = $this->scopeConfig->getValue(
                'dcw_revenue_ranking/general/sales_period_days',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) ?: 30;
            
            // Update revenue data
            $updatedCount = $this->revenueRankingResource->updateBaseRevenue($salesPeriodDays);
            
            // Update the revenue_ranking product attribute
            $this->updateProductAttribute($salesPeriodDays);
            
            $this->logger->info("Revenue ranking update completed. Updated {$updatedCount} products and product attributes");
            
        } catch (\Exception $e) {
            $this->logger->error('Error in revenue ranking update: ' . $e->getMessage());
        }
    }

    /**
     * Update product attribute with revenue data
     */
    private function updateProductAttribute($salesPeriodDays)
    {
		$connection = $this->resourceConnection->getResourceConnection()->getConnection();
		$attribute = $this->eavConfig->getAttribute('catalog_product', 'revenue_ranking');
		$attributeId = $attribute->getId();
		$backendType = $attribute->getBackendType();
		$eavTable  = $this->resourceConnection->getResourceConnection()->getTableName('catalog_product_entity_' . $backendType);
		$this->createLogs('backendType='.$backendType);
		$this->createLogs('eavTable='.$eavTable);
        try {
            $this->logger->info('Starting product attribute update for revenue_ranking');
            $storeIds = array_keys($this->storeManager->getStores());
			$storeIds[] = 0;
            $revenueData = $this->revenueRankingResource->getRevenueDataForAttribute();
            
            if (empty($revenueData)) {
                $this->logger->info('No revenue data found for product attribute update');
                return;
            }
            
            $this->logger->info('Found ' . count($revenueData) . ' products with revenue data');
            
            foreach ($storeIds as $storeId) {
                foreach ($revenueData as $productId => $revenue) {
					// Update attribute value
					$sql = "
						UPDATE {$eavTable} AS t
						JOIN catalog_product_entity AS e ON t.row_id = e.row_id
						SET t.value = :value
						WHERE t.attribute_id = :attribute_id
						  AND e.entity_id = :entity_id
						  AND t.store_id = :store_id
					";

					$connection->query($sql, [
						'value'        => $revenue,
						'attribute_id' => $attributeId,
						'entity_id'    => $productId,
						'store_id'     => $storeId
					]);
                }
            }
            $this->logger->info('Product attribute update completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Error updating product attribute: ' . $e->getMessage());
        }
    }
	/**
     * Create a log entry
     *
     * @param string $msg
     */
    public function createLogs($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/RevenueRankingCron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
