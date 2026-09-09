<?php
/**
 * OrderRestrictions Rule ResourceModel.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */
namespace Dotcomweavers\OrderRestrictions\Model\ResourceModel;

class Rule extends \Magento\Rule\Model\ResourceModel\AbstractResource
{

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('orderrestriction_rules', 'restriction_id');
    }
}
