<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml\CircularDesignerTool\Edit;

/**
 * CircularDesignerTool Tabs.
 */

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('circulardesignertool_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('CircularDesignerTool Information'));
    }
}
