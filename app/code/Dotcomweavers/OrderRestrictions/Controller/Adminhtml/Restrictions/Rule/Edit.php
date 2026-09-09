<?php
/**
 * OrderRestrictions Edit Controller.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */

namespace Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule;

class Edit extends \Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule
{
    /**
     * Rule edit action
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Dotcomweavers\OrderRestrictions\Model\Rule $model */
        $model = $this->ruleFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getRestrictionId()) {
                $this->messageManager->addErrorMessage(__('This rule no longer exists.'));
                $this->_redirect('orderrestrictions/*');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = $this->_session->getPageData(true);

        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');

        $this->coreRegistry->register('current_rule', $model);

        $this->_initAction();
        $this->_view->getLayout()
            ->getBlock('restrictions_rule_edit')
            ->setData('action', $this->getUrl('orderrestrictions/*/save'));

        $this->_addBreadcrumb($id ? __('Edit Rule') : __('New Rule'), $id ? __('Edit Rule') : __('New Rule'));

        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getRestrictionId() ? $model->getName() : __('New Rule')
        );
        $this->_view->renderLayout();
    }
}
