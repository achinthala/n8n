<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking as RevenueRankingResource;
use Magento\Catalog\Model\ResourceModel\Product\ActionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Dcw\RevenueRanking\Model\RevenueRankingService;
use Magento\Eav\Model\Config as EavConfig;

class RefreshRevenue extends Command
{
    /**
     * @var RevenueRankingResource
     */
    protected $revenueRankingResource;

    /**
     * @var ActionFactory
     */
    protected $productActionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;
	
	protected $revenueRankingService;
	
	/**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @param RevenueRankingResource $revenueRankingResource
     * @param ActionFactory $productActionFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        RevenueRankingResource $revenueRankingResource,
        ActionFactory $productActionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
		RevenueRankingService $revenueRankingService,
		EavConfig $eavConfig
    ) {
        $this->revenueRankingResource = $revenueRankingResource;
        $this->productActionFactory = $productActionFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
		$this->revenueRankingService = $revenueRankingService;
		$this->eavConfig = $eavConfig;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('revenue:refresh')
            ->setDescription('Refresh revenue ranking data')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Number of days to look back for revenue calculation',
                30
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force refresh even if Buy % is disabled'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Starting revenue ranking refresh...</info>');

        try {
            // Get sales period from option or configuration
            $salesPeriodDays = $input->getOption('days');
            $force = $input->getOption('force');

            // Check if Buy % is enabled (unless forced)
            if (!$force && !$this->revenueRankingResource->isBuyPercentageEnabled()) {
                $output->writeln('<error>Buy % feature is disabled. Use --force to override.</error>');
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }

            $output->writeln("<info>Using sales period: {$salesPeriodDays} days</info>");

            // Update revenue data
            $output->writeln('<info>Updating revenue data...</info>');
            $updatedCount = $this->revenueRankingResource->updateBaseRevenue($salesPeriodDays);
            $output->writeln("<info>Updated {$updatedCount} revenue records</info>");

            // Update product attributes
            $output->writeln('<info>Updating product attributes...</info>');
            $attributeUpdatedCount = $this->updateProductAttributes();
            $output->writeln("<info>Updated {$attributeUpdatedCount} product attributes</info>");

            $output->writeln('<info>Revenue ranking refresh completed successfully!</info>');

            $this->logger->info('Revenue ranking CLI refresh completed', [
                'updated_count' => $updatedCount,
                'attribute_updated_count' => $attributeUpdatedCount,
                'sales_period_days' => $salesPeriodDays,
                'forced' => $force
            ]);

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error refreshing revenue data: ' . $e->getMessage() . '</error>');
            
            $this->logger->error('Revenue ranking CLI refresh failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * Update product attributes with revenue data
     *
     * @return int
     */
    private function updateProductAttributes()
    {
		$connection = $this->revenueRankingService->getResourceConnection()->getConnection();
		$attribute = $this->eavConfig->getAttribute('catalog_product', 'revenue_ranking');
		$attributeId = $attribute->getId();
		$backendType = $attribute->getBackendType();
		$eavTable  = $this->revenueRankingService->getResourceConnection()->getTableName('catalog_product_entity_' . $backendType);
        try {
            $storeIds = array_keys($this->storeManager->getStores());
			$storeIds[] = 0;
            $revenueData = $this->revenueRankingResource->getRevenueDataForAttribute();
            
            if (empty($revenueData)) {
                return 0;
            }
            
            $updatedCount = 0;
            
            foreach ($storeIds as $storeId) {
                foreach ($revenueData as $productId => $revenue) {
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
                    $updatedCount++;
                }
            }
            
            return $updatedCount;
        } catch (\Exception $e) {
            $this->logger->error('Error updating product attributes in CLI', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
