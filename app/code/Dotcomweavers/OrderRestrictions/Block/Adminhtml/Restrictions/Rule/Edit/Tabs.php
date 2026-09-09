<?php
/**
 * OrderRestrictions Tab block.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */
namespace Dotcomweavers\OrderRestrictions\Block\Adminhtml\Restrictions\Rule\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('rules_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Rules'));
    }
}
