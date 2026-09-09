<?php
namespace Dcw\RevenueRanking\Model;

use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking as RevenueRankingResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\ActionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Service class that groups multiple dependencies
 * used for Revenue Ranking logic.
 *
 * This helps reduce the number of constructor parameters
 * in controllers or other classes.
 */
class RevenueRankingService
{
    public function __construct(
        private RevenueRankingResource $revenueRankingResource,
        private ScopeConfigInterface $scopeConfig,
        private ResourceConnection $resourceConnection,
        private ActionFactory $productActionFactory,
        private StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Access the RevenueRanking resource model
     */
    public function getRevenueRankingResource(): RevenueRankingResource
    {
        return $this->revenueRankingResource;
    }

    /**
     * Access scope configuration
     */
    public function getScopeConfig(): ScopeConfigInterface
    {
        return $this->scopeConfig;
    }

    /**
     * Access DB resource connection
     */
    public function getResourceConnection(): ResourceConnection
    {
        return $this->resourceConnection;
    }

    /**
     * Access Product Action Factory
     */
    public function getProductActionFactory(): ActionFactory
    {
        return $this->productActionFactory;
    }

    /**
     * Access Store Manager
     */
    public function getStoreManager(): StoreManagerInterface
    {
        return $this->storeManager;
    }

    /**
     * Get sales period days value from config
     * Falls back to 30 if not set.
     */
    public function getSalesPeriodDays(): int
    {
        return (int) ($this->scopeConfig->getValue(
            'dcw_revenue_ranking/general/sales_period_days',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?: 30);
    }
}
