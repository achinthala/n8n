<?php
namespace Dcw\CustomTax\Block\Adminhtml;

/**
 * CustomTax grid container.
 */

class CustomTax extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_customtax';
        $this->_blockGroup = 'Dcw_CustomTax';
        $this->_headerText = __('CustomTax');
        $this->_addButtonLabel = __('Add New CustomTax');
        parent::_construct();
    }
}
