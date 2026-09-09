<?php
declare(strict_types=1);

namespace Dcw\AmastyQuote\Plugin\Ui;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * Adds amasty_quote_increment_id from sales_order to the admin order grid payload.
 */
class SalesOrderGridDataProviderPlugin
{
    private const GRID_DATA_SOURCE = 'sales_order_grid_data_source';

    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function afterGetData(DataProvider $subject, array $data): array
    {
        if ($subject->getName() !== self::GRID_DATA_SOURCE) {
            return $data;
        }
        if (!isset($data['items']) || !is_array($data['items'])) {
            return $data;
        }

        $ids = [];
        foreach ($data['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = isset($item['entity_id']) ? (int) $item['entity_id'] : 0;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return $data;
        }

        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        $select = $connection->select()
            ->from($orderTable, ['entity_id', 'amasty_quote_increment_id'])
            ->where('entity_id IN (?)', $ids);

        $map = [];
        foreach ($connection->fetchAll($select) as $row) {
            $map[(int) $row['entity_id']] = $row['amasty_quote_increment_id'];
        }

        foreach ($data['items'] as &$item) {
            if (!is_array($item)) {
                continue;
            }
            $id = isset($item['entity_id']) ? (int) $item['entity_id'] : 0;
            if ($id > 0 && isset($map[$id])) {
                $item['amasty_quote_increment_id'] = $map[$id];
            }
        }
        unset($item);

        return $data;
    }
}
