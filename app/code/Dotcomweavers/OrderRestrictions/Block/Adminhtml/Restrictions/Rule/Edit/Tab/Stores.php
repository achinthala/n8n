<?php
/**
 * OrderRestrictions Tab Stores block.
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

class Stores extends Generic implements TabInterface
{
    /**
     * @var Store
     **/
    protected $_systemStore;
    
    /**
     * @var Options
     **/
    protected $_groups;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Customer\Ui\Component\Listing\Column\Group\Options $groups
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Customer\Ui\Component\Listing\Column\Group\Options $groups,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_groups = $groups;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Stores & Customers Group');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Stores & Customers Group');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
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

        $fieldset = $form->addFieldset('stores_fieldset', ['legend' => __('Stores & Customer Groups Information')]);

        $fieldset->addField(
            'stores',
            'multiselect',
            [
             'name'     => 'stores[]',
             'label'    => __('Store Views'),
             'title'    => __('Store Views'),
             'required' => false,
             'values'   => $this->_systemStore->getStoreValuesForForm(false, true),
            ]
        );

        $fieldset->addField(
            'customer_groups',
            'multiselect',
            [
             'name'     => 'customer_groups[]',
             'label'    => __('Customer Groups'),
             'title'    => __('Customer Groups'),
             'required' => false,
             'values'   => $this->_groups->toOptionArray(),
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
