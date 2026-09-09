<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml;

/**
 * CircularDesignerTool grid container.
 */

class CircularDesignerToolTab extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_CircularDesignerToolTab';
        $this->_blockGroup = 'Dcw_CircularDesignerTool';
        $this->_headerText = __('CircularDesignerTool Tab');
        //$this->_addButtonLabel = __('Add New CircularDesignerTool Tab');
        parent::_construct();

        $this->buttonList->remove('add');
    }
}
