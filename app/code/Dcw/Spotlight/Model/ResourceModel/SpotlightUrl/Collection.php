<?php

namespace Dcw\Spotlight\Model\ResourceModel\SpotlightUrl;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Dcw\Spotlight\Model\SpotlightUrl as Model;
use Dcw\Spotlight\Model\ResourceModel\SpotlightUrl as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
