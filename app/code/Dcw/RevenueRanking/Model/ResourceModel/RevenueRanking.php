<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class RevenueRanking extends AbstractDb
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        ScopeConfigInterface $scopeConfig,
        $connectionName = null
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('custom_product_revenue_ranking', 'id');
    }

    /**
     * Get revenue ranking by product ID
     *
     * @param int $productId
     * @return array|null
     */
    public function getByProductId($productId)
    {
        $connection = $this->getConnection();
        
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('product_id = ?', $productId);
        
        return $connection->fetchRow($select);
    }

    /**
     * Get top products by revenue
     *
     * @param int $limit
     * @return array
     */
    public function getTopProductsByRevenue($limit = 10)
    {
        $connection = $this->getConnection();
        
        $select = $connection->select()
            ->from($this->getMainTable())
            ->order('adjusted_revenue DESC')
            ->limit($limit);
        
        return $connection->fetchAll($select);
    }

    /**
     * Update base revenue data
     * Preserves existing manual adjustment values
     *
     * @param int $salesPeriodDays
     * @return int
     */
    public function updateBaseRevenue($salesPeriodDays)
    {
        $connection = $this->getConnection();

        // Create temporary table with new revenue data
        $tempTableName = $this->getMainTable() . '_temp';
        $connection->dropTable($tempTableName);

        $createTempTableQuery = "
			CREATE TEMPORARY TABLE {$tempTableName} (
				product_id INT UNSIGNED NOT NULL,
				sku VARCHAR(255),
				base_revenue DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
				adjusted_revenue DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
				PRIMARY KEY (product_id)
			)
		";
        $connection->query($createTempTableQuery);

        // Insert all catalog-search visible products with revenue (or 0 if none)
        $insertTempQuery = "
			INSERT INTO {$tempTableName} (product_id, sku, base_revenue, adjusted_revenue)
			SELECT 
				COALESCE(cpsl.parent_id, soi.product_id) as product_id,
				cpe.sku,
				SUM(soi.row_total - COALESCE(soi.discount_amount, 0)) as base_revenue,
				SUM(soi.row_total - COALESCE(soi.discount_amount, 0)) as adjusted_revenue
			FROM sales_order_item soi
			INNER JOIN sales_order so ON soi.order_id = so.entity_id
			LEFT JOIN catalog_product_super_link cpsl ON soi.product_id = cpsl.product_id
			INNER JOIN catalog_product_entity cpe ON COALESCE(cpsl.parent_id, soi.product_id) = cpe.entity_id
			WHERE so.status = 'complete'
			AND so.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
			GROUP BY COALESCE(cpsl.parent_id, soi.product_id), cpe.sku
		";
        $connection->query($insertTempQuery, [$salesPeriodDays]);
        
        $insertMissingProductsQuery = "
			INSERT INTO {$tempTableName} (product_id, sku, base_revenue, adjusted_revenue)
			SELECT 
				cpe.entity_id AS product_id,
				cpe.sku,
				0 AS base_revenue,
				0 AS adjusted_revenue
			FROM catalog_product_entity cpe
			INNER JOIN catalog_product_entity_int cpei
				ON cpe.row_id = cpei.row_id
			   AND cpei.attribute_id = (
					SELECT attribute_id 
					FROM eav_attribute 
					WHERE attribute_code='visibility' AND entity_type_id=4
			   )
			   AND cpei.value IN (2,3,4)
			   AND cpei.store_id = 0
			WHERE NOT EXISTS (
				SELECT 1 
				FROM {$tempTableName} t
				WHERE t.product_id = cpe.entity_id
			)
		";
        $connection->query($insertMissingProductsQuery);

        // Update existing records with new base revenue, preserving manual adjustments
        $updateQuery = "
			UPDATE {$this->getMainTable()} main
			INNER JOIN {$tempTableName} temp ON main.product_id = temp.product_id
			SET 
				main.sku = temp.sku,
				main.base_revenue = temp.base_revenue,
				main.adjusted_revenue = temp.base_revenue + COALESCE(main.manual_adjustment, 0),
				main.last_updated = NOW()
		";
        $connection->query($updateQuery);

        // Insert new records that don't exist yet
        $insertNewQuery = "
			INSERT INTO {$this->getMainTable()} (product_id, sku, base_revenue, adjusted_revenue, manual_adjustment, last_updated)
			SELECT 
				temp.product_id,
				temp.sku,
				temp.base_revenue,
				temp.adjusted_revenue,
				NULL as manual_adjustment,
				NOW() as last_updated
			FROM {$tempTableName} temp
			LEFT JOIN {$this->getMainTable()} main ON temp.product_id = main.product_id
			WHERE main.product_id IS NULL
		";
        $connection->query($insertNewQuery);

        // Clean up temporary table
        $connection->dropTable($tempTableName);

        // Get count of updated records
        $countSelect = $connection->select()
            ->from($this->getMainTable(), ['COUNT(*)'])
            ->where('base_revenue > 0');

        return (int) $connection->fetchOne($countSelect);
    }

    /**
     * Get revenue data for updating product attributes
     * Uses adjusted_revenue (base_revenue + manual_adjustment)
     *
     * @return array
     */
    public function getRevenueDataForAttribute()
    {
        $connection = $this->getConnection();
        
        $select = $connection->select()
            ->from($this->getMainTable(), [
                'product_id',
                'revenue_value' => 'adjusted_revenue'
            ])
            ->order('adjusted_revenue DESC');
        
        $result = $connection->fetchPairs($select);
        
        return $result;
    }

    /**
     * Get revenue ranking with products
     *
     * @param int $limit
     * @return array
     */
    public function getRevenueRankingWithProducts($limit = 20)
    {
        $connection = $this->getConnection();
        
        $select = $connection->select()
            ->from(['main_table' => $this->getMainTable()])
            ->joinLeft(
                ['cpe' => $connection->getTableName('catalog_product_entity')],
                'main_table.product_id = cpe.entity_id',
                ['sku']
            )
            ->joinLeft(
                ['cpev' => $connection->getTableName('catalog_product_entity_varchar')],
                'cpe.entity_id = cpev.entity_id AND cpev.attribute_id = (
                    SELECT attribute_id FROM eav_attribute 
                    WHERE attribute_code = "name" 
                    AND entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = "catalog_product")
                )',
                ['name' => 'value']
            )
            ->order('main_table.base_revenue DESC')
            ->limit($limit);
        
        return $connection->fetchAll($select);
    }

    /**
     * Check if Buy % feature is enabled
     *
     * @return bool
     */
    public function isBuyPercentageEnabled()
    {
        return (bool) $this->scopeConfig->getValue(
            'dcw_revenue_ranking/general/enable_buy_percentage',
            ScopeInterface::SCOPE_STORE
        );
    }
}
