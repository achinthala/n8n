<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\ResourceModel\Region\Grid;

use Dcw\ShipRegionAvailability\Model\ResourceModel\Region;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        $mainTable = Region::TABLE_NAME,
        $resourceModel = Region::class,
        $identifierName = 'region_id',
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    /**
     * Keyword search (LIKE) — reliable without depending on FULLTEXT index / min word length.
     */
    public function addFullTextFilter(string $value): self
    {
        $value = trim($value);
        if ($value === '') {
            return $this;
        }

        $like = '%' . $this->escapeLikeValue($value) . '%';
        $conditions = [];

        foreach ($this->getKeywordSearchFields() as $field) {
            $conditions[] = $this->_getConditionSql($field, ['like' => $like]);
        }

        if ($conditions !== []) {
            $this->getSelect()->where(implode(' OR ', $conditions));
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    private function getKeywordSearchFields(): array
    {
        return [
            'main_table.code',
            'main_table.name',
        ];
    }

    private function escapeLikeValue(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
