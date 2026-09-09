<?php

namespace Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerTool;

/**
 * CircularDesignerTool Collection
 */

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_storeViewId = null;

    protected $_storeManager;

    protected $_addedTable = [];

    protected $_isLoadSliderTitle = false;

    protected function _construct()
    {
        $this->_init(
            \Dcw\CircularDesignerTool\Model\CircularDesignerTool::class,
            \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerTool::class
        );
    }

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_storeManager = $storeManager;

        if ($storeViewId = $this->_storeManager->getStore()->getId()) {
            $this->_storeViewId = $storeViewId;
        }
    }

    /*protected function _beforeLoad()
    {
        return parent::_beforeLoad();
    }*/

    public function setOrderRandByFaqId()
    {
        $this->getSelect()->orderRand('main_table.id');

        return $this;
    }

    public function getStoreViewId()
    {
        return $this->_storeViewId;
    }

    public function setStoreViewId($storeViewId)
    {
        $this->_storeViewId = $storeViewId;

        return $this;
    }

    public function getConnection()
    {
        return $this->getResource()->getConnection();
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($storeViewId = $this->getStoreViewId()) {
            foreach ($this->_items as $item) {
                $item->setStoreViewId($storeViewId)->getStoreViewValue();
            }
        }

        return $this;
    }
}
