<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml;

/**
 * CircularDesignerTool grid container.
 */

class CircularDesignerTool extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_CircularDesignerTool';
        $this->_blockGroup = 'Dcw_CircularDesignerTool';
        $this->_headerText = __('CircularDesignerTool');
        $this->_addButtonLabel = __('Add New CircularDesignerTool');
        parent::_construct();
    }
}
