<?php
/**
 * Copyright © Dcw. All rights reserved.
 * Resource model for product data operations
 */
declare(strict_types=1);

namespace Dcw\MinimumPriceCron\Model\ResourceModel;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class ProductData
 * 
 * Handles all database operations for product data
 */
class ProductData
{
    /**
     * Catalog product entity type ID
     */
    private const PRODUCT_ENTITY_TYPE_ID = 4;

    /**
     * Time interval for recently updated products (in hours)
     */
    private const UPDATE_INTERVAL_HOURS = 1;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var EavConfig
     */
    private EavConfig $eavConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        EavConfig $eavConfig,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
        $this->connection = $this->resourceConnection->getConnection();
    }

    /**
     * Get recently updated product row IDs (including parent configurables)
     *
     * @return array
     */
    public function getRecentlyUpdatedProductRowIds(): array
    {
        $select = $this->connection->select()
            ->from(
                ['derived' => new \Zend_Db_Expr('(
                    SELECT COALESCE(cpsl.parent_id, cpe.row_id) AS row_id,
                           cpe.entity_id AS entity_id,
                           cpe.sku,
                           cpe.updated_at
                    FROM ' . $this->connection->getTableName('catalog_product_entity') . ' AS cpe
                    LEFT JOIN ' . $this->connection->getTableName('catalog_product_super_link') . ' AS cpsl
                        ON cpsl.product_id = cpe.entity_id
                    WHERE cpe.updated_at >= DATE_SUB(NOW(), INTERVAL ' . self::UPDATE_INTERVAL_HOURS . ' HOUR)
                )')],
                ['row_id']
            )
            ->group('row_id');

        return $this->connection->fetchCol($select);
    }

    /**
     * Get all required attribute IDs
     *
     * @return array
     * @throws LocalizedException
     */
    public function getRequiredAttributeIds(): array
    {
        $attributeCodes = [
            'price',
            'min_simple_product_size',
            'incstores_pim_exact_width_inches',
            'incstores_pim_exact_length_inches',
            'incstores_pim_coverage',
            'incstores_pim_exact_height_inches',
            'incstores_pim_shipping_program',
            'incstores_pim_calculator_type'
        ];

        $attributeIds = [];

        foreach ($attributeCodes as $code) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
            if ($attribute && $attribute->getId()) {
                $key = str_replace('incstores_pim_', '', str_replace('_inches', '', $code));
                $attributeIds[$key] = (int)$attribute->getId();
            }
        }

        return $attributeIds;
    }

    /**
     * Get simple product data with all attributes
     *
     * @param array $productRowIds
     * @param array $attributeIds
     * @return array
     */
    public function getSimpleProductData(array $productRowIds, array $attributeIds): array
    {
        if (empty($productRowIds)) {
            return [];
        }

        $select = $this->connection->select()
            ->from(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                [
                    'configurable_id' => 'cpe.entity_id',
                    'configurable_row_id' => 'cpe.row_id'
                ]
            )
            ->join(
                ['cpsl' => $this->connection->getTableName('catalog_product_super_link')],
                'cpsl.parent_id = cpe.row_id',
                []
            )
            ->join(
                ['cpe_simple' => $this->connection->getTableName('catalog_product_entity')],
                'cpe_simple.entity_id = cpsl.product_id',
                [
                    'simple_id' => 'entity_id',
                    'simple_sku' => 'sku'
                ]
            )
            ->join(
                ['cped' => $this->connection->getTableName('catalog_product_entity_decimal')],
                'cped.row_id = cpe_simple.row_id AND cped.attribute_id = ' . $attributeIds['price'],
                ['price' => 'value']
            )
            ->where('cpe.type_id = ?', 'configurable')
            ->where('cpe.row_id IN (?)', $productRowIds);

        // Left join for width
        if (isset($attributeIds['exact_width'])) {
            $select->joinLeft(
                ['width' => $this->connection->getTableName('catalog_product_entity_varchar')],
                'width.row_id = cpe_simple.row_id AND width.attribute_id = ' . $attributeIds['exact_width'],
                ['exact_width' => 'value']
            );
        }

        // Left join for length
        if (isset($attributeIds['exact_length'])) {
            $select->joinLeft(
                ['length' => $this->connection->getTableName('catalog_product_entity_varchar')],
                'length.row_id = cpe_simple.row_id AND length.attribute_id = ' . $attributeIds['exact_length'],
                ['exact_length' => 'value']
            );
        }

        // Left join for coverage
        if (isset($attributeIds['coverage'])) {
            $select->joinLeft(
                ['coverage' => $this->connection->getTableName('catalog_product_entity_varchar')],
                'coverage.row_id = cpe_simple.row_id AND coverage.attribute_id = ' . $attributeIds['coverage'],
                ['coverage' => 'value']
            );
        }

        // Left join for calculator_type
        if (isset($attributeIds['calculator_type'])) {
            $select->joinLeft(
                ['calc_type' => $this->connection->getTableName('catalog_product_entity_int')],
                'calc_type.row_id = cpe_simple.row_id AND calc_type.attribute_id = ' . $attributeIds['calculator_type'],
                []
            )
            ->joinLeft(
                ['calc_eao' => $this->connection->getTableName('eav_attribute_option')],
                'calc_type.value = calc_eao.option_id',
                []
            )
            ->joinLeft(
                ['calc_eaov' => $this->connection->getTableName('eav_attribute_option_value')],
                'calc_eao.option_id = calc_eaov.option_id AND calc_eaov.store_id = 0',
                ['calculator_type' => 'calc_eaov.value']
            );
        }

        // Left join for shipping program
        if (isset($attributeIds['shipping_program'])) {
            $select->joinLeft(
                ['shipping' => $this->connection->getTableName('catalog_product_entity_int')],
                'shipping.row_id = cpe_simple.row_id AND shipping.attribute_id = ' . $attributeIds['shipping_program'],
                []
            )
            ->joinLeft(
                ['eao' => $this->connection->getTableName('eav_attribute_option')],
                'shipping.value = eao.option_id',
                []
            )
            ->joinLeft(
                ['eaov' => $this->connection->getTableName('eav_attribute_option_value')],
                'eao.option_id = eaov.option_id AND eaov.store_id = 0',
                ['shipping_program' => new \Zend_Db_Expr('CONCAT(eaov.option_id, ";", eaov.value)')]
            );
        }

        return $this->connection->fetchAll($select);
    }

    /**
     * Get stock status for products
     *
     * @param array $productRowIds
     * @return array [product_id => stock_status]
     */
    public function getStockStatus(array $productRowIds): array
    {
        if (empty($productRowIds)) {
            return [];
        }

        $select = $this->connection->select()
            ->from(
                ['stock_item' => $this->connection->getTableName('cataloginventory_stock_item')],
                ['product_id']
            )
            ->join(
                ['stock_status' => $this->connection->getTableName('cataloginventory_stock_status')],
                'stock_item.product_id = stock_status.product_id',
                ['stock_status']
            )
            ->join(
                ['cpr' => $this->connection->getTableName('catalog_product_relation')],
                'cpr.child_id = stock_item.product_id',
                []
            )
            ->where('stock_item.website_id = ?', 0)
            ->where('cpr.parent_id IN (?)', $productRowIds);

        $result = $this->connection->fetchPairs($select);
        return $result ?: [];
    }

    /**
     * Get product status (enabled/disabled)
     *
     * @param array $productRowIds
     * @return array [product_id => status]
     */
    public function getProductStatus(array $productRowIds): array
    {
        if (empty($productRowIds)) {
            return [];
        }

        $statusAttributeId = $this->eavConfig->getAttribute(Product::ENTITY, 'status')->getId();

        $select = $this->connection->select()
            ->from(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                ['entity_id']
            )
            ->join(
                ['cpei' => $this->connection->getTableName('catalog_product_entity_int')],
                'cpei.row_id = cpe.row_id',
                ['value']
            )
            ->join(
                ['cpr' => $this->connection->getTableName('catalog_product_relation')],
                'cpr.child_id = cpe.entity_id',
                []
            )
            ->where('cpe.type_id = ?', 'simple')
            ->where('cpei.attribute_id = ?', $statusAttributeId)
            ->where('cpr.parent_id IN (?)', $productRowIds);

        $result = $this->connection->fetchPairs($select);
        return $result ?: [];
    }

    /**
     * Get special prices for products
     *
     * @param array $productRowIds
     * @return array [product_id => special_price]
     */
    public function getSpecialPrices(array $productRowIds): array
    {
        if (empty($productRowIds)) {
            return [];
        }

        $specialPriceAttributeId = $this->eavConfig->getAttribute(Product::ENTITY, 'special_price')->getId();

        $select = $this->connection->select()
            ->from(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                ['entity_id']
            )
            ->join(
                ['cped' => $this->connection->getTableName('catalog_product_entity_decimal')],
                'cpe.row_id = cped.row_id',
                ['value']
            )
            ->join(
                ['cpr' => $this->connection->getTableName('catalog_product_relation')],
                'cpr.child_id = cpe.entity_id',
                []
            )
            ->where('cpe.type_id = ?', 'simple')
            ->where('cped.attribute_id = ?', $specialPriceAttributeId)
            ->where('cped.value IS NOT NULL')
            ->where('cpr.parent_id IN (?)', $productRowIds);

        $result = $this->connection->fetchPairs($select);
        return $result ?: [];
    }

    /**
     * Get tier prices for products (customer group 0 - ALL GROUPS)
     *
     * @param array $productRowIds
     * @return array [product_id => tier_price]
     */
    public function getTierPrices(array $productRowIds): array
    {
        if (empty($productRowIds)) {
            return [];
        }

        $select = $this->connection->select()
            ->from(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                ['entity_id']
            )
            ->join(
                ['cpetp' => $this->connection->getTableName('catalog_product_entity_tier_price')],
                'cpetp.row_id = cpe.row_id',
                ['value']
            )
            ->join(
                ['cpr' => $this->connection->getTableName('catalog_product_relation')],
                'cpr.child_id = cpe.entity_id',
                []
            )
            ->where('cpe.type_id = ?', 'simple')
            ->where('cpetp.customer_group_id = ?', 0)
            ->where('cpr.parent_id IN (?)', $productRowIds);

        $result = $this->connection->fetchPairs($select);
        return $result ?: [];
    }

    /**
     * Bulk update varchar attributes
     *
     * @param array $data
     * @return int Number of rows affected
     */
    public function bulkUpdateVarcharAttributes(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $tableName = $this->connection->getTableName('catalog_product_entity_varchar');

        // Delete existing values
        foreach ($data as $row) {
            $this->connection->delete(
                $tableName,
                [
                    'attribute_id = ?' => $row['attribute_id'],
                    'row_id = ?' => $row['row_id']
                ]
            );
        }

        // Prepare data for insert
        $insertData = [];
        foreach ($data as $row) {
            $insertData[] = [
                'attribute_id' => $row['attribute_id'],
                'store_id' => 0,
                'row_id' => $row['row_id'],
                'value' => $row['value']
            ];
        }

        // Bulk insert
        return $this->connection->insertMultiple($tableName, $insertData);
    }

    /**
     * Bulk update int attributes
     *
     * @param array $data
     * @return int Number of rows affected
     */
    public function bulkUpdateIntAttributes(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $tableName = $this->connection->getTableName('catalog_product_entity_int');

        // Delete existing values
        foreach ($data as $row) {
            $this->connection->delete(
                $tableName,
                [
                    'attribute_id = ?' => $row['attribute_id'],
                    'row_id = ?' => $row['row_id']
                ]
            );
        }

        // Prepare data for insert
        $insertData = [];
        foreach ($data as $row) {
            $insertData[] = [
                'attribute_id' => $row['attribute_id'],
                'store_id' => 0,
                'row_id' => $row['row_id'],
                'value' => $row['value']
            ];
        }

        // Bulk insert
        return $this->connection->insertMultiple($tableName, $insertData);
    }

    /**
     * Get recently updated simple product entity IDs
     *
     * @return array
     */
    public function getRecentlyUpdatedSimpleProductIds(): array
    {
        $select = $this->connection->select()
            ->from(
                $this->connection->getTableName('catalog_product_entity'),
                ['entity_id']
            )
            ->where('type_id = ?', 'simple')
            ->where('updated_at >= DATE_SUB(NOW(), INTERVAL ' . self::UPDATE_INTERVAL_HOURS . ' HOUR)');

        return $this->connection->fetchCol($select);
    }
}

