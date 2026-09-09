<?php
/**
 * Copyright © Dcw. All rights reserved.
 * Cron job to update configurable products with minimum priced simple product data
 */
declare(strict_types=1);

namespace Dcw\MinimumPriceCron\Cron;

use Dcw\MinimumPriceCron\Model\ProductDataUpdater;
use Dcw\MinimumPriceCron\Model\StockManager;
use Psr\Log\LoggerInterface;

/**
 * Class MinimumPricedSimpleProduct
 * 
 * Updates configurable products with data from their minimum-priced simple product variants
 * including dimensions, coverage, and shipping program information
 */
class MinimumPricedSimpleProduct
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ProductDataUpdater
     */
    private ProductDataUpdater $productDataUpdater;

    /**
     * @var StockManager
     */
    private StockManager $stockManager;

    /**
     * @param LoggerInterface $logger
     * @param ProductDataUpdater $productDataUpdater
     * @param StockManager $stockManager
     */
    public function __construct(
        LoggerInterface $logger,
        ProductDataUpdater $productDataUpdater,
        StockManager $stockManager
    ) {
        $this->logger = $logger;
        $this->productDataUpdater = $productDataUpdater;
        $this->stockManager = $stockManager;
    }

    /**
     * Execute cron job to update minimum priced product data
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $this->logger->info('Starting Minimum Priced Simple Product Cron Job');

            // Update configurable products with minimum priced simple product data
            $updatedCount = $this->productDataUpdater->updateConfigurableProducts();
            
            // Update stock status for modified simple products
            $stockUpdatedCount = $this->stockManager->updateStockForModifiedProducts();

            $this->logger->info(
                'Minimum Priced Simple Product Cron Job Completed',
                [
                    'configurable_products_updated' => $updatedCount,
                    'simple_products_stock_updated' => $stockUpdatedCount
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error in Minimum Priced Simple Product Cron Job',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }
}
