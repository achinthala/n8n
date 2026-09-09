<?php
/**
 * OrderRestrictions NewConditionHtml Controller.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */

namespace Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule;

class NewConditionHtml extends \Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule
{
    /**
     * New condition html action
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = $this->_objectManager->create(
            $type
        )->setId(
            $id
        )->setType(
            $type
        )->setRule(
            $this->ruleFactory->create()
        )->setPrefix(
            'conditions'
        );
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof \Magento\Rule\Model\Condition\AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $model->setFormName("custom_condition_form");
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }
}
