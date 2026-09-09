<?php
namespace Dcw\CustomTax\Block\Adminhtml\CustomTax\Edit;

/**
 * CustomTax Tabs.
 */

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customtax_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Import Custom Tax'));
    }
}
