<?php
namespace Dcw\CircularDesignerTool\Model\ResourceModel;

/**
 * CircularDesignerTool Resource Model
 */

class CircularDesignerTool extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('cdt_attribute', 'cdt_attribute_id');
    }
}
