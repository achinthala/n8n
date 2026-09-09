<?php
declare(strict_types=1);

namespace Dcw\QuoteOrderGridTotal\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Returns quote grid total as JSON by querying quote table directly.
 * Public action + CSRF bypass so AJAX from the grid works without secret key errors.
 */
class GridTotal extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
{
    public const ADMIN_RESOURCE = 'Amasty_RequestQuote::quote';

    /** @var string[] */
    protected $_publicActions = ['gridTotal'];

    private ResourceConnection $resource;
    private LoggerInterface $logger;
	private $timezone;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        LoggerInterface $logger,
		TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->logger = $logger;
		$this->timezone = $timezone;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function execute(): Json
    {
        $totalAmount = 0.0;
        $totalCount = 0;

        try {
            // Quote/Amasty quote tables live on checkout resource in many setups.
            $connection = $this->resource->getConnection('checkout');
            $quoteTable = $this->resource->getTableName('quote');
            $amastyQuoteTable = $this->resource->getTableName('amasty_quote');

            $select = $connection->select()
                ->from(
                    ['main_table' => $quoteTable],
                    [
                        'total_amount' => 'COALESCE(SUM(main_table.base_grand_total), 0)',
                        'total_count' => 'COUNT(DISTINCT main_table.entity_id)',
                    ]
                );

            $this->applyFiltersToSelect($select, $quoteTable, $amastyQuoteTable, $connection);

            $row = $connection->fetchRow($select);
            if ($row !== false) {
                $totalAmount = (float)($row['total_amount'] ?? 0);
                $totalCount = (int)($row['total_count'] ?? 0);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'QuoteOrderGridTotal GridTotal: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(
            [
                'grid_total' => [
                    'total_amount' => $totalAmount,
                    'base_total_amount' => $totalAmount,
                    'total_count' => $totalCount,
                ],
            ]
        );

        return $result;
    }

    private function applyFiltersToSelect(
        Select $select,
        string $tableName,
        string $amastyQuoteTable,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ): void
    {
        $request = $this->getRequest();
        $mainColumns = [];
        $amastyColumns = [];
        try {
            $mainColumns = array_keys($connection->describeTable($tableName));
        } catch (\Throwable $e) {
            $mainColumns = [];
        }
        try {
            $amastyColumns = array_keys($connection->describeTable($amastyQuoteTable));
        } catch (\Throwable $e) {
            $amastyColumns = [];
        }

        $filters = $request->getParam('filters', []);
        if (!is_array($filters)) {
            $filters = [];
        }

        // Date range (Amasty often uses submitted_date in filter UI).
        [$fromDateValue, $toDateValue] = $this->extractDateFilterRange($filters);

        [$dateAlias, $dateField] = $this->resolveDateFieldAlias($mainColumns, $amastyColumns);

        if (!empty($fromDateValue)) {
            $fromDate = $this->parseGridDate((string)$fromDateValue, true);
            if ($fromDate && $dateAlias !== null && $dateField !== null) {
                if ($dateAlias === 'amasty_quote') {
                    $this->joinAmastyQuoteIfNeeded($select, $amastyQuoteTable);
                }
                $select->where($dateAlias . '.' . $dateField . ' >= ?', $fromDate);
            }
        }
        if (!empty($toDateValue)) {
            $toDate = $this->parseGridDate((string)$toDateValue, false);
            if ($toDate && $dateAlias !== null && $dateField !== null) {
                if ($dateAlias === 'amasty_quote') {
                    $this->joinAmastyQuoteIfNeeded($select, $amastyQuoteTable);
                }
                $select->where($dateAlias . '.' . $dateField . ' <= ?', $toDate);
            }
        }

        foreach ($filters as $field => $value) {
            if ($field === 'placeholder' || $field === 'created_at') {
                continue;
            }
            if (in_array($field, ['submitted_date', 'submited_date'], true)) {
                // Already handled as date range.
                continue;
            }
            [$alias, $canApply] = $this->resolveFilterFieldAlias($field, $mainColumns, $amastyColumns);
            if (!$canApply) {
                continue;
            }

            if (is_array($value)) {
                if ($value === []) {
                    continue;
                }
                if ($alias === 'amasty_quote') {
                    $this->joinAmastyQuoteIfNeeded($select, $amastyQuoteTable);
                }
                $select->where($connection->quoteIdentifier($alias . '.' . $field) . ' IN (?)', $value);
                continue;
            }

            if ($value === null) {
                continue;
            }

            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }

            if ($field === 'status') {
                $mapped = $this->normalizeStatusFilterValue($value);
                if ($mapped !== null) {
                    $value = (string)$mapped;
                }
            }

            if ($alias === 'amasty_quote') {
                $this->joinAmastyQuoteIfNeeded($select, $amastyQuoteTable);
            }
            if (is_numeric($value)) {
                $select->where($connection->quoteIdentifier($alias . '.' . $field) . ' = ?', $value);
            } else {
                $select->where($connection->quoteIdentifier($alias . '.' . $field) . ' LIKE ?', '%' . $value . '%');
            }
        }

        // Support filters[field]=... sent as literal query keys (fallback).
        foreach ($request->getParams() as $key => $value) {
            if (strpos((string)$key, 'filters[') !== 0) {
                continue;
            }
            if ($value === '' || $value === null) {
                continue;
            }

            if (!preg_match('/filters\[([^\]]+)\]/', (string)$key, $m)) {
                continue;
            }
            $field = $m[1] ?? '';
            if ($field === '' || $field === 'placeholder' || $field === 'created_at') {
                continue;
            }
            if (in_array($field, ['submitted_date', 'submited_date'], true)) {
                continue;
            }
            [$alias, $canApply] = $this->resolveFilterFieldAlias($field, $mainColumns, $amastyColumns);
            if (!$canApply) {
                continue;
            }

            $val = trim((string)$value);
            if ($val === '') {
                continue;
            }

            if ($field === 'status') {
                $mapped = $this->normalizeStatusFilterValue($val);
                if ($mapped !== null) {
                    $val = (string)$mapped;
                }
            }

            if ($alias === 'amasty_quote') {
                $this->joinAmastyQuoteIfNeeded($select, $amastyQuoteTable);
            }
            if (is_numeric($val)) {
                $select->where($connection->quoteIdentifier($alias . '.' . $field) . ' = ?', $val);
            } else {
                $select->where($connection->quoteIdentifier($alias . '.' . $field) . ' LIKE ?', '%' . $val . '%');
            }
        }
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function extractDateFilterRange(array $filters): array
    {
        $fromDateValue = null;
        $toDateValue = null;

        $dateKeys = ['submited_date', 'submitted_date', 'created_at'];
        foreach ($dateKeys as $dateKey) {
            if (isset($filters[$dateKey]) && is_array($filters[$dateKey])) {
                $fromDateValue = $filters[$dateKey]['from'] ?? $fromDateValue;
                $toDateValue = $filters[$dateKey]['to'] ?? $toDateValue;
            } elseif (isset($filters[$dateKey]) && is_string($filters[$dateKey])) {
                [$rangeFrom, $rangeTo] = $this->parseDateRangeString($filters[$dateKey]);
                if ($fromDateValue === null || $fromDateValue === '') {
                    $fromDateValue = $rangeFrom;
                }
                if ($toDateValue === null || $toDateValue === '') {
                    $toDateValue = $rangeTo;
                }
            }

            $fromKey = $dateKey . '[from]';
            $toKey = $dateKey . '[to]';
            if (($fromDateValue === null || $fromDateValue === '') && isset($filters[$fromKey])) {
                $fromDateValue = $filters[$fromKey];
            }
            if (($toDateValue === null || $toDateValue === '') && isset($filters[$toKey])) {
                $toDateValue = $filters[$toKey];
            }
        }

        return [$fromDateValue, $toDateValue];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function resolveDateFieldAlias(array $mainColumns, array $amastyColumns): array
    {
        foreach (['submited_date', 'submitted_date', 'created_at'] as $field) {
            if (in_array($field, $mainColumns, true)) {
                return ['main_table', $field];
            }
            if (in_array($field, $amastyColumns, true)) {
                return ['amasty_quote', $field];
            }
        }

        return [null, null];
    }

    private function normalizeStatusFilterValue(string $value): ?int
    {
        if ($value === '' || is_numeric($value)) {
            return null;
        }

        $map = [
            'pending' => 0,
            'approved' => 1,
            'rejected' => 2,
            'canceled' => 4,
            'cancelled' => 4,
            'expired' => 5
        ];

        $key = strtolower(trim($value));
        return $map[$key] ?? null;
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function resolveFilterFieldAlias(string $field, array $mainColumns, array $amastyColumns): array
    {
        if (in_array($field, $mainColumns, true)) {
            return ['main_table', true];
        }
        if (in_array($field, $amastyColumns, true)) {
            return ['amasty_quote', true];
        }

        return ['', false];
    }

    private function joinAmastyQuoteIfNeeded(Select $select, string $amastyQuoteTable): void
    {
        $from = $select->getPart(Select::FROM);
        if (isset($from['amasty_quote'])) {
            return;
        }

        $select->joinLeft(
            ['amasty_quote' => $amastyQuoteTable],
            'amasty_quote.quote_id = main_table.entity_id',
            []
        );
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function parseDateRangeString(string $value): array
    {
        $value = trim($value);
        if ($value === '' || strpos($value, '-') === false) {
            return [null, null];
        }

        $parts = preg_split('/\s*-\s*/', $value);
        if (!is_array($parts) || count($parts) < 2) {
            return [null, null];
        }

        return [trim((string)$parts[0]), trim((string)$parts[1])];
    }

    private function parseGridDate(string $value, bool $startOfDay): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            $dt = $this->timezone->date($value);
            $dt->setTime($startOfDay ? 0 : 23, $startOfDay ? 0 : 59, $startOfDay ? 0 : 59);
			// Convert to UTC after setting correct local time
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}

