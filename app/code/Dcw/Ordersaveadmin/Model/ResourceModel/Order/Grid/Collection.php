<?php

namespace Dcw\Ordersaveadmin\Model\ResourceModel\Order\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OriginalCollection;
use Psr\Log\LoggerInterface as Logger;

/**
 * Order grid extended collection
 */
class Collection extends OriginalCollection
{
    protected $helper;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'sales_order_grid',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order::class
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    protected function _renderFiltersBefore()
    {
        $joinTable = $this->getTable('sales_order');
        $this->getSelect()->joinLeft($joinTable, 'main_table.entity_id = sales_order.entity_id', ['admin_user_assistance']);
		
		$where = $this->getSelect()->getPart(\Zend_Db_Select::WHERE);

        // Clear the existing where conditions
        $this->getSelect()->reset(\Zend_Db_Select::WHERE);

        // Re-add the existing where conditions while changing created_at to use the sales_order table
        foreach ($where as $condition) {
            // Change created_at condition to sales_order.created_at if exists
            if (strpos($condition, 'created_at') !== false) {
				$replace = '`main_table`.`created_at`';
                $condition = preg_replace('/[`]?created_at[`]?/', $replace, $condition);
            }
			$condition = trim($condition);
            if (substr($condition, 0, 3) === 'AND') {
                $condition = substr($condition, 3);
            }
            $this->getSelect()->where($condition);
        }
        parent::_renderFiltersBefore();
    }
}