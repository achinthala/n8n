<?php
/**
 * OrderRestrictions Index Controller.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */

namespace Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule;

class Index extends \Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule
{
    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Restrictions Rules'), __('Restrictions Rules'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Restrictions Rules'));
        $this->_view->renderLayout('root');
    }

    /**
     * Returns result of current user permission check on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotcomweavers_OrderRestrictions::add_row');
    }
}
