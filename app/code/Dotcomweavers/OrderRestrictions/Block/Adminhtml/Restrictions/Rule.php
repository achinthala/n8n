<?php
/**
 * OrderRestrictions Rule block.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */
namespace Dotcomweavers\OrderRestrictions\Block\Adminhtml\Restrictions;

class Rule extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'restrictions_rule';
        $this->_headerText = __('Restrictions Rules');
        $this->_addButtonLabel = __('Add New Rule');
        parent::_construct();
    }
}
