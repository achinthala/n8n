<?php

namespace Dcw\Spotlight\Model;

use Magento\Framework\Model\AbstractModel;
use Dcw\Spotlight\Model\ResourceModel\SpotlightUrl as ResourceModel;

class SpotlightUrl extends AbstractModel
{
    public function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
