<?php
namespace Dcw\CircularDesignerTool\Model\ResourceModel;

/**
 * CircularDesignerTool Resource Model
 */

class CircularDesignerToolTab extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('cdt_tab', 'cdt_tab_id');
    }
}
