<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Sales\Order\Grid;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

/**
 * Filters admin order grid by pending-review JSON when a restriction rule is chosen in the UI.
 */
class CollectionPlugin
{
    private const JOIN_ALIAS = 'so_dcw_pending_reason';

    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @param mixed $condition
     */
    public function aroundAddFieldToFilter(Collection $subject, callable $proceed, $field, $condition = null)
    {
        if ($field !== 'dcw_pending_review_reasons') {
            return $proceed($field, $condition);
        }

        $parsed = $this->parseFilterCondition($condition);
        if ($parsed === null) {
            return $subject;
        }

        $this->joinSalesOrderTable($subject);

        $conn = $subject->getConnection();
        if ($parsed['type'] === 'legacy_code') {
            $code = (string) $parsed['code'];
            $like = '%"' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $code) . '"%';
            $subject->getSelect()->where(
                self::JOIN_ALIAS . '.dcw_pending_review_reasons LIKE ?',
                $like
            );

            return $subject;
        }

        $rid = (string) (int) $parsed['id'];
        $likeOr = $conn->quoteInto(
            self::JOIN_ALIAS . '.dcw_pending_review_reasons LIKE ?',
            '%"restriction_id":' . $rid . ',%'
        ) . ' OR ' . $conn->quoteInto(
            self::JOIN_ALIAS . '.dcw_pending_review_reasons LIKE ?',
            '%"restriction_id":' . $rid . '}%'
        ) . ' OR ' . $conn->quoteInto(
            self::JOIN_ALIAS . '.dcw_pending_review_reasons LIKE ?',
            '%"restriction_id":' . $rid . ']%'
        );

        $subject->getSelect()->where('(' . $likeOr . ')');

        return $subject;
    }

    private function joinSalesOrderTable(Collection $collection): void
    {
        $from = $collection->getSelect()->getPart(Select::FROM);
        if (isset($from[self::JOIN_ALIAS])) {
            return;
        }

        $salesOrder = $this->resourceConnection->getTableName('sales_order');
        $collection->getSelect()->joinInner(
            [self::JOIN_ALIAS => $salesOrder],
            'main_table.entity_id = ' . self::JOIN_ALIAS . '.entity_id',
            []
        );
    }

    /**
     * @return array{type: 'rule_id', id: int}|array{type: 'legacy_code', code: string}|null
     */
    private function parseFilterCondition($condition): ?array
    {
        if ($condition === null || $condition === '') {
            return null;
        }
        if (is_array($condition)) {
            if (isset($condition['eq'])) {
                $value = $condition['eq'];
            } elseif (isset($condition['in']) && is_array($condition['in']) && $condition['in'] !== []) {
                $value = reset($condition['in']);
            } else {
                return null;
            }
        } else {
            $value = $condition;
        }
        if ($value === '' || $value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? ['type' => 'rule_id', 'id' => $id] : null;
        }

        $code = trim((string) $value);

        return $code !== '' ? ['type' => 'legacy_code', 'code' => $code] : null;
    }
}
