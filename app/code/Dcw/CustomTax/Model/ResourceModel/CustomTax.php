<?php
namespace Dcw\CustomTax\Model\ResourceModel;

/**
 * CustomTax Resource Model
 */

class CustomTax extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('dcw_customtax', 'id');
    }
}
