<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml\CircularDesignerToolTab\Edit\Tab;

use Dcw\CircularDesignerTool\Model\Status;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * CircularDesignerTool Edit tab.
 */

class CircularDesignerTool extends Generic implements TabInterface
{
    protected $_objectFactory;

    protected $_circulardesignertool;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\DataObjectFactory $objectFactory,
        \Dcw\CircularDesignerTool\Model\CircularDesignerTool $circulardesignertool,
        array $data = []
    ) {
        $this->_objectFactory = $objectFactory;
        $this->_circulardesignertool = $circulardesignertool;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('page.title')->setPageTitle($this->getPageTitle());

        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock(
                \Dcw\CircularDesignerTool\Block\Adminhtml\Form\Renderer\Fieldset\Element::class,
                $this->getNameInLayout().'_fieldset_element'
            )
        );

        return $this;
    }

    protected function _prepareForm()
    {
        $circulardesignertoolAttributes = $this->_circulardesignertool->getStoreAttributes();
        $circulardesignertoolAttributesInStores = ['store_id' => ''];

        foreach ($circulardesignertoolAttributes as $circulardesignertoolAttribute) {
            $circulardesignertoolAttributesInStores[$circulardesignertoolAttribute.'_in_store'] = '';
        }

        $dataObj = $this->_objectFactory->create(
            ['data' => $circulardesignertoolAttributesInStores]
        );
        $model = $this->_coreRegistry->registry('circulardesignertooltab');

        $dataObj->addData($model->getData());

        $storeViewId = $this->getRequest()->getParam('store');
        
        $form = $this->_formFactory->create();
        
        $form->setHtmlIdPrefix($this->_circulardesignertool->getFormFieldHtmlIdPrefix());
        
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('CircularDesignerTool Information')]);

        if ($model->getId()) {
            $fieldset->addField('cdt_tab_id', 'hidden', ['name' => 'id']);
        }

        $elements = [];
        $elements['cdt_tab_title'] = $fieldset->addField(
            'cdt_tab_title',
            'text',
            [
                'name' => 'cdt_tab_title',
                'label' => __('Tab Title'),
                'title' => __('Tab Title'),
                'required' => true,
            ]
        );
        
        $elements['cdt_tab_sort_order'] = $fieldset->addField(
            'cdt_tab_sort_order',
            'text',
            [
                'name' => 'cdt_tab_sort_order',
                'label' => __('Tab Sort Order'),
                'title' => __('Tab Sort Order'),
                'required' => true,
            ]
        );
        
        $form->addValues($dataObj->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getCircularDesignerTool()
    {
        return $this->_coreRegistry->registry('circulardesignertooltab');
    }

    public function getPageTitle()
    {
        return $this->getCircularDesignerTool()->getId()?
        __("EDIT CircularDesignerTool '%1'", $this->escapeHtml($this->getCircularDesignerTool()->getcdt_tab_title())) :
         __('NEW CircularDesignerTool');
    }

    public function getTabLabel()
    {
        return __('CircularDesignerTool Information');
    }

    public function getTabTitle()
    {
        return __('CircularDesignerTool Information');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
