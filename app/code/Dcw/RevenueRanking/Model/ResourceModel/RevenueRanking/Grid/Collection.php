<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'custom_product_revenue_ranking',
        $resourceModel = \Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking::class
    ) {
        $this->logger = $logger;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    public function getItems()
    {
        $this->logger->info('Revenue Ranking Collection: Getting items', [
            'main_table' => $this->getMainTable(),
            'select_sql' => (string)$this->getSelect()
        ]);
        
        // SKU is now stored directly in the table, no need for JOIN
        $items = parent::getItems();
        
        $this->logger->info('Revenue Ranking Collection: Found ' . count($items) . ' items');
        
        return $items;
    }
}
