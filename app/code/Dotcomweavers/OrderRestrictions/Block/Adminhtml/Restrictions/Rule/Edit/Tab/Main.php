<?php
/**
 * OrderRestrictions Tab Main block.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */
namespace Dotcomweavers\OrderRestrictions\Block\Adminhtml\Restrictions\Rule\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Main extends Generic implements TabInterface
{
    /**
     * Prepare TabLabel
     *
     * @return TabLabel
     * */
    public function getTabLabel()
    {
        return __('General');
    }

    /**
     * Prepare TabTitle
     *
     * @return TabTitle
     */
    public function getTabTitle()
    {
        return __('General');
    }

    /**
     * Check showtab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check for field hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return Generic
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_rule');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $fieldset->addField('restriction_id', 'hidden', ['name' => 'restriction_id']);
        }

        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'label' => __('Name'), 'title' => __('Name'), 'required' => true]
        );

        $fieldset->addField(
            'enabled',
            'select',
            [
                'label' => __('Enabled'),
                'title' => __('Enabled'),
                'name' => 'enabled',
                'required' => false,
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        if (!$model->getId()) {
            $model->setData('enabled', '1');
        }

        $fieldset->addField(
            'internal_notes',
            'textarea',
            [
                'name' => 'internal_notes',
                'label' => __('Internal Notes'),
                'title' => __('Internal Notes'),
                'style' => 'height: 100px;'
            ]
        );

        $fieldset->addField(
            'message',
            'textarea',
            [
                'name' => 'message',
                'label' => __('Message'),
                'title' => __('Message'),
                'style' => 'height: 100px;'
            ]
        );

        $form->setValues($model->getData());

        if ($model->isReadonly()) {
            foreach ($fieldset->getElements() as $element) {
                $element->setReadonly(true, true);
            }
        }

        $this->setForm($form);

        $this->_eventManager->dispatch('adminhtml_restrictions_rule_edit_tab_main_prepare_form', ['form' => $form]);

        return parent::_prepareForm();
    }
}
