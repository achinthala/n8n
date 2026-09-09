<?php
declare(strict_types=1);

namespace Dcw\SalesOrderGridTotal\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Returns grid total as JSON by querying sales_order_grid table directly.
 * Public action + CSRF bypass so AJAX from the Orders grid works without secret key errors.
 */
class GridTotal extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * Allow this action without secret key in URL (for AJAX calls from grid).
     *
     * @var string[]
     */
    protected $_publicActions = ['gridTotal'];

    /**
     * Database resource connection.
     *
     * @var ResourceConnection
     */
    private $resource;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Timezone helper (store/admin timezone aware).
     *
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * GridTotal constructor.
     *
     * @param Context $context
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezone
     */
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

    /**
     * Skip CSRF validation for this AJAX endpoint (read-only GET).
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Create CSRF validation exception.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Execute action and return grid totals as JSON.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $totalAmount = 0.0;
        $totalCount = 0;

        try {
            $connection = $this->resource->getConnection();
            $gridTable = $this->resource->getTableName('sales_order_grid');

            $select = $connection->select()
                ->from(
                    $gridTable,
                    [
                        'total_amount' => 'COALESCE(SUM(base_grand_total), 0)',
                        'total_count' => 'COUNT(*)',
                    ]
                );

            $this->applyFiltersToSelect($select, $gridTable);

            $row = $connection->fetchRow($select);
            if ($row !== false) {
                $totalAmount = (float) ($row['total_amount'] ?? 0);
                $totalCount = (int) ($row['total_count'] ?? 0);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'SalesOrderGridTotal GridTotal: ' . $e->getMessage(),
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

    /**
     * Apply request filters to select (status, date range, store, etc.).
     *
     * @param \Magento\Framework\DB\Select $select
     * @param string $tableName
     * @return void
     */
    private function applyFiltersToSelect(
        $select,
        string $tableName
    ): void {
        $request = $this->getRequest();
        $connection = $this->resource->getConnection();

        $status = $request->getParam('status');
        if ($status === null) {
            $filters = $request->getParam('filters', []);
            $status = is_array($filters)
                ? ($filters['status'] ?? null)
                : null;
        }

        if ($status !== null && $status !== '') {
            if (is_array($status)) {
                $select->where(
                    $connection->quoteInto('status IN (?)', $status)
                );
            } else {
                $select->where('status = ?', $status);
            }
        }

        $filters = $request->getParam('filters', []);
        if (is_array($filters)) {
            $fromDateValue = $filters['created_at']['from'] ?? null;
            $toDateValue = $filters['created_at']['to'] ?? null;
            // Fallback: form may send created_at[from] / created_at[to] as literal keys
            if (($fromDateValue === null || $fromDateValue === '') && isset($filters['created_at[from]'])) {
                $fromDateValue = $filters['created_at[from]'];
            }
            if (($toDateValue === null || $toDateValue === '') && isset($filters['created_at[to]'])) {
                $toDateValue = $filters['created_at[to]'];
            }
            if (!empty($fromDateValue)) {
                $fromDate = $this->parseGridDate(
                    $fromDateValue,
                    true
                );
                if ($fromDate) {
                    $select->where('created_at >= ?', $fromDate);
                }
            }

            if (!empty($toDateValue)) {
                $toDate = $this->parseGridDate(
                    $toDateValue,
                    false
                );
                if ($toDate) {
                    $select->where('created_at <= ?', $toDate);
                }
            }

            if (!empty($filters['store_id'])) {
                $select->where(
                    'store_id = ?',
                    $filters['store_id']
                );
            }
        }

        $params = $request->getParams();
        foreach ($params as $key => $value) {
            if (
                strpos($key, 'filters[') === 0 &&
                $value !== '' &&
                $value !== null
            ) {
                preg_match('/filters\[([^\]]+)\]/', $key, $m);
                if (!empty($m[1]) && $m[1] !== 'placeholder') {
                    $field = $m[1];
                    if (
                        in_array(
                            $field,
                            ['status', 'store_id', 'entity_id'],
                            true
                        )
                    ) {
                        $select->where(
                            $connection->quoteIdentifier($field) . ' = ?',
                            $value
                        );
                    }
                }
            }
        }
    }

    /**
     * Parse grid date value into DB format (Y-m-d H:i:s).
     *
     * @param string $value
     * @param bool $startOfDay
     * @return string|null
     */
    private function parseGridDate(
        string $value,
        bool $startOfDay
    ): ?string {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
			$dt = $this->timezone->date($value);
            $dt->setTime(
                $startOfDay ? 0 : 23,
                $startOfDay ? 0 : 59,
                $startOfDay ? 0 : 59
            );
			// Convert to UTC after setting correct local time
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}