<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection
     */
    protected function _construct()
    {
        $this->_init(
            \Dcw\RevenueRanking\Model\RevenueRanking::class,
            \Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking::class
        );
    }
}
