<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\ResourceModel\Zip\Grid;

use Dcw\ShipRegionAvailability\Model\ResourceModel\Region;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip;
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
        $mainTable = Zip::TABLE_NAME,
        $resourceModel = Zip::class,
        $identifierName = 'zip_id',
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

    protected function _initSelect(): void
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['region_table' => $this->getTable(Region::TABLE_NAME)],
            'main_table.region_id = region_table.region_id',
            [
                'region_code' => 'code',
                'region_name' => 'name',
            ]
        );
        $this->addFilterToMap('zip_id', 'main_table.zip_id');
        $this->addFilterToMap('zip_code', 'main_table.zip_code');
        $this->addFilterToMap('region_code', 'region_table.code');
        $this->addFilterToMap('region_name', 'region_table.name');
        $this->addFilterToMap('status', 'main_table.status');
    }

    /**
     * Keyword search for ZIP grid (UNIQUE on zip_code prevents a separate FULLTEXT index).
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
            'main_table.zip_code',
            'region_table.code',
            'region_table.name',
        ];
    }

    private function escapeLikeValue(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
