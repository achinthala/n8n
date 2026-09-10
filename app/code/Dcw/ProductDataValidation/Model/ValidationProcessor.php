<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous catalog scan: Enabled + In-Stock/Backorder, exclude samples, report-only.
 */
class ValidationProcessor
{
    public function __construct(
        private readonly Config $config,
        private readonly JobManager $jobManager,
        private readonly Validator $validator,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process a single pending job to completion (or failure).
     */
    public function process(Job $job): void
    {
        $jobId = (int) $job->getJobId();
        $started = microtime(true);

        try {
            $attributes = $this->config->getRequiredAttributes();
            if ($attributes === []) {
                throw new \RuntimeException('No required attributes configured.');
            }

            $storeIds = $this->resolveStoreIds($job);
            $this->jobManager->markRunning($job);

            $eligibleIds = $this->getEligibleProductIds();
            $totalEligible = count($eligibleIds);
            $job->setData('total_eligible', $totalEligible);
            $job->setData('store_ids', implode(',', $storeIds));
            $this->jobManager->save($job);

            $this->logger->info('Validation job started', [
                'job_id' => $jobId,
                'total_eligible' => $totalEligible,
                'attributes' => $attributes,
                'store_ids' => $storeIds,
            ]);

            $checked = 0;
            $issueRows = 0;
            $productsWithIssues = [];
            $breakdown = [];
            $batchSize = $this->config->getBatchSize();
            $chunks = array_chunk($eligibleIds, $batchSize);
            $now = $this->dateTime->gmtDate();

            foreach ($chunks as $chunkIds) {
                $rowsToInsert = [];
                foreach ($storeIds as $storeId) {
                    $collection = $this->createProductCollection($chunkIds, $storeId, $attributes);
                    /** @var Product $product */
                    foreach ($collection as $product) {
                        $missing = $this->validator->getMissingAttributes($product, $attributes);
                        if ($missing === []) {
                            continue;
                        }
                        $productId = (int) $product->getId();
                        $productsWithIssues[$productId] = true;
                        $issueRows++;
                        foreach ($missing as $code) {
                            $breakdown[$code] = ($breakdown[$code] ?? 0) + 1;
                        }
                        $rowsToInsert[] = [
                            'job_id' => $jobId,
                            'product_id' => $productId,
                            'sku' => (string) $product->getSku(),
                            'product_name' => (string) $product->getName(),
                            'store_id' => $storeId,
                            'missing_attributes' => implode(',', $missing),
                            'status' => 'missing',
                            'created_at' => $now,
                        ];
                    }
                }

                if ($rowsToInsert !== []) {
                    $this->bulkInsertResults($rowsToInsert);
                }

                $checked += count($chunkIds);
                $job->setData('products_checked', $checked);
                $job->setData('products_with_issues', count($productsWithIssues));
                $job->setData('products_passed', max(0, $checked - count($productsWithIssues)));
                $job->setAttributeBreakdown($breakdown);
                $this->jobManager->save($job);
            }

            $withIssuesCount = count($productsWithIssues);
            $job->setData('products_checked', $totalEligible);
            $job->setData('products_with_issues', $withIssuesCount);
            $job->setData('products_passed', max(0, $totalEligible - $withIssuesCount));
            $job->setAttributeBreakdown($breakdown);
            $job->setData('execution_time', round(microtime(true) - $started, 3));
            $this->jobManager->markCompleted($job);

            $this->logger->info('Validation job completed', [
                'job_id' => $jobId,
                'products_checked' => $totalEligible,
                'products_with_issues' => $withIssuesCount,
                'issue_rows' => $issueRows,
                'execution_time' => $job->getData('execution_time'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Validation job failed', [
                'job_id' => $jobId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $job->setData('execution_time', round(microtime(true) - $started, 3));
            $this->jobManager->markFailed(
                $job,
                (string) __('Unable to complete product data validation. See var/log/product_data_validation.log.')
            );
        }
    }

    /**
     * @return int[]
     */
    private function resolveStoreIds(Job $job): array
    {
        $fromJob = (string) $job->getData('store_ids');
        if ($fromJob !== '') {
            return array_values(array_unique(array_map('intval', explode(',', $fromJob))));
        }

        return $this->config->getStoreIds();
    }

    /**
     * Enabled + In Stock (incl. backorder-capable) product IDs, excluding samples.
     *
     * @return int[]
     */
    public function getEligibleProductIds(): array
    {
        $connection = $this->getConnection();
        $cpe = $this->resourceConnection->getTableName('catalog_product_entity');
        $cpei = $this->resourceConnection->getTableName('catalog_product_entity_int');
        $stockStatus = $this->resourceConnection->getTableName('cataloginventory_stock_status');
        $stockItem = $this->resourceConnection->getTableName('cataloginventory_stock_item');

        $statusAttrId = $this->getStatusAttributeId($connection);
        $sampleSetId = $this->config->getSampleAttributeSetId();

        $select = $connection->select()
            ->from(['e' => $cpe], ['entity_id'])
            ->joinInner(
                ['status' => $cpei],
                'status.entity_id = e.entity_id AND status.attribute_id = ' . (int) $statusAttrId
                . ' AND status.store_id = 0',
                []
            )
            ->joinInner(
                ['ss' => $stockStatus],
                'ss.product_id = e.entity_id AND ss.stock_id = ' . (int) Stock::DEFAULT_STOCK_ID,
                []
            )
            ->joinLeft(
                ['si' => $stockItem],
                'si.product_id = e.entity_id AND si.stock_id = ' . (int) Stock::DEFAULT_STOCK_ID
                . ' AND si.website_id = 0',
                []
            )
            ->where('status.value = ?', Status::STATUS_ENABLED)
            ->where('(ss.stock_status = 1 OR (si.backorders > 0 AND si.is_in_stock = 1))')
            ->distinct(true);

        if ($sampleSetId > 0) {
            $select->where('e.attribute_set_id != ?', $sampleSetId);
        }

        $ids = $connection->fetchCol($select);

        return array_map('intval', $ids);
    }

    private function getStatusAttributeId(AdapterInterface $connection): int
    {
        $eav = $this->resourceConnection->getTableName('eav_attribute');
        $entityType = $this->resourceConnection->getTableName('eav_entity_type');
        $select = $connection->select()
            ->from(['ea' => $eav], ['attribute_id'])
            ->join(
                ['et' => $entityType],
                'et.entity_type_id = ea.entity_type_id',
                []
            )
            ->where('et.entity_type_code = ?', 'catalog_product')
            ->where('ea.attribute_code = ?', 'status')
            ->limit(1);

        return (int) $connection->fetchOne($select);
    }

    /**
     * @param int[] $productIds
     * @param string[] $attributes
     */
    private function createProductCollection(array $productIds, int $storeId, array $attributes): ProductCollection
    {
        $selectAttributes = array_values(array_unique(array_merge(['sku', 'name', 'price'], $attributes)));
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addIdFilter($productIds);
        $collection->addAttributeToSelect($selectAttributes);

        return $collection;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function bulkInsertResults(array $rows): void
    {
        $table = $this->resourceConnection->getTableName('dcw_product_data_validation_result');
        $this->getConnection()->insertMultiple($table, $rows);
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }
}
