<?php

declare(strict_types=1);

namespace Dcw\ReportLogCleanup\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Cron job to remove report/log data older than configured retention days from:
 * - report_viewed_product_index (Magento viewed products report)
 * - mst_search_report_log (Mirasvit search report log)
 * - avatax_log (Avalara AvaTax log)
 *
 * Enabled and retention period are configurable in Stores > Configuration > DCW > Report Log Cleanup.
 */
class CleanupReportLogs
{
    private const XML_PATH_ENABLED = 'dcw_reportlogcleanup/general/enabled';
    private const XML_PATH_RETENTION_DAYS = 'dcw_reportlogcleanup/general/retention_days';
    private const DEFAULT_RETENTION_DAYS = 30;
    private const MINIMUM_RETENTION_DAYS = 30;

    /**
     * @param ResourceConnection $resource
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute cron: delete records older than configured retention days from all tables.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $retentionDays = $this->getRetentionDays();
        if ($retentionDays < self::MINIMUM_RETENTION_DAYS) {
            $this->logger->warning(
                'Report log cleanup: retention_days must be at least ' . self::MINIMUM_RETENTION_DAYS . ', skipping.'
            );
            return;
        }

        $connection = $this->resource->getConnection();

        try {
            $viewedDeleted = $this->cleanupViewedProductIndex($connection, $retentionDays);
            $searchDeleted = $this->cleanupSearchReportLog($connection, $retentionDays);
            $avataxDeleted = $this->cleanupAvataxLog($connection, $retentionDays);

            if ($viewedDeleted > 0 || $searchDeleted > 0 || $avataxDeleted > 0) {
                $this->logger->info(
                    'Report log cleanup: removed ' . $viewedDeleted
                    . ' from report_viewed_product_index, ' . $searchDeleted
                    . ' from mst_search_report_log, ' . $avataxDeleted
                    . ' from avatax_log (retention: ' . $retentionDays . ' days).',
                    [
                        'viewed_deleted' => $viewedDeleted,
                        'search_deleted' => $searchDeleted,
                        'avatax_deleted' => $avataxDeleted,
                        'retention_days' => $retentionDays,
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Report log cleanup failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    private function getRetentionDays(): int
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_RETENTION_DAYS, ScopeInterface::SCOPE_STORE);
        $days = $value !== null ? (int) $value : self::DEFAULT_RETENTION_DAYS;
        return max(self::MINIMUM_RETENTION_DAYS, $days);
    }

    /**
     * Delete records from report_viewed_product_index older than retention days.
     * Uses added_at column (Magento standard).
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param int $retentionDays
     * @return int Number of deleted rows
     */
    private function cleanupViewedProductIndex($connection, int $retentionDays): int
    {
        $table = $this->resource->getTableName('report_viewed_product_index');
        $where = $connection->quoteInto(
            'added_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            $retentionDays
        );
        return (int) $connection->delete($table, $where);
    }

    /**
     * Delete records from mst_search_report_log older than retention days.
     * Uses created_at column (Mirasvit SearchReport).
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param int $retentionDays
     * @return int Number of deleted rows
     */
    private function cleanupSearchReportLog($connection, int $retentionDays): int
    {
        $table = $this->resource->getTableName('mst_search_report_log');
        $where = $connection->quoteInto(
            'created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            $retentionDays
        );
        return (int) $connection->delete($table, $where);
    }

    /**
     * Delete records from avatax_log older than retention days.
     * Uses created_at column (Avalara AvaTax log).
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param int $retentionDays
     * @return int Number of deleted rows
     */
    private function cleanupAvataxLog($connection, int $retentionDays): int
    {
        $table = $this->resource->getTableName('avatax_log');
        $where = $connection->quoteInto(
            'created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            $retentionDays
        );
        return (int) $connection->delete($table, $where);
    }
}
