<?php

namespace Dcw\Spotlight\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SpotlightUrl extends AbstractDb
{
    public function _construct()
    {
        $this->_init('dcw_spotlight_tmp', 'id');
    }
}
