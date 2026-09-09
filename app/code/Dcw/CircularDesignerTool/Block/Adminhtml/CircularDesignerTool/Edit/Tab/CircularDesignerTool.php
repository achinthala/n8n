<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml\CircularDesignerTool\Edit\Tab;

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
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerToolTab\CollectionFactory
        $circulardesignertooltabCollectionFactory,
        array $data = []
    ) {
        $this->_objectFactory = $objectFactory;
        $this->_circulardesignertool = $circulardesignertool;
        $this->_circulardesignertooltabCollectionFactory = $circulardesignertooltabCollectionFactory;
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
        $model = $this->_coreRegistry->registry('circulardesignertool');

        $dataObj->addData($model->getData());

        $storeViewId = $this->getRequest()->getParam('store');
        
        $form = $this->_formFactory->create();
        
        $form->setHtmlIdPrefix($this->_circulardesignertool->getFormFieldHtmlIdPrefix());
        
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('CircularDesignerTool Information')]);

        if ($model->getId()) {
            $fieldset->addField('cdt_attribute_id', 'hidden', ['name' => 'id']);
        }

        $elements = [];

        $elements['cdt_tab_id'] = $fieldset->addField(
            'cdt_tab_id',
            'select',
            [
                'label' => __('Select Tab'),
                'title' => __('Select Tab'),
                'name' => 'cdt_tab_id',
                'options' => $this->getTabOptions(),
            ]
        );

        $elements['cdt_attribute_title'] = $fieldset->addField(
            'cdt_attribute_title',
            'text',
            [
                'name' => 'cdt_attribute_title',
                'label' => __('Attribute Title'),
                'title' => __('Attribute Title'),
                'required' => true,
            ]
        );

        $elements['cdt_attribute_value_display'] = $fieldset->addField(
            'cdt_attribute_value_display',
            'text',
            [
                'name' => 'cdt_attribute_value_display',
                'label' => __('Attribute Value Display'),
                'title' => __('Attribute Value Display'),
                'required' => true,
            ]
        );
        
        $elements['cdt_attribute_value'] = $fieldset->addField(
            'cdt_attribute_value',
            'text',
            [
                'name' => 'cdt_attribute_value',
                'label' => __('Attribute Value'),
                'title' => __('Attribute Value'),
                'required' => true,
            ]
        );

        $elements['description'] = $fieldset->addField(
            'description',
            'text',
            [
                'name' => 'description',
                'label' => __('Attribute Description'),
                'title' => __('Attribute Description'),
                'required' => false,
            ]
        );

        $elements['sections'] = $fieldset->addField(
            'sections',
            'text',
            [
                'name' => 'sections',
                'label' => __('Sections'),
                'title' => __('Sections'),
                'required' => false,
            ]
        );

        $elements['sections'] = $fieldset->addField(
            'validation',
            'text',
            [
                'name' => 'validation',
                'label' => __('Validation'),
                'title' => __('Validation'),
                'required' => false,
            ]
        );

        $elements['image'] =  $fieldset->addField(
            'image',
            'image',
            [
                'name' => 'image',
                'label' => __('Upload Image'),
                'title' => __('Upload Image'),
                'required' => false,
            ]
        );

        $elements['cdt_attribute_sort_order'] = $fieldset->addField(
            'cdt_attribute_sort_order',
            'text',
            [
                'name' => 'cdt_attribute_sort_order',
                'label' => __('Sort Order'),
                'title' => __('Sort Order'),
                'required' => true,
            ]
        );

        $form->addValues($dataObj->getData());
        $this->setForm($form);

        $p = $form->getElement('image')->getValue();
        if (!empty($p)) {
            $p = $form->getElement('image')->setValue('designer_tool/'.$p);
        }
        
        return parent::_prepareForm();
    }

    public function getTabOptions()
    {
        $circulardesignertooltabCollection = $this->_circulardesignertooltabCollectionFactory->create();
        $circulardesignertooltabCollection->setOrder('cdt_tab_sort_order', 'ASC');
        $tabArr = [];
        foreach ($circulardesignertooltabCollection as $tab) {
            $tabArr[$tab->getCdtTabId()] = $tab->getCdtTabTitle();
        }
        return $tabArr;
    }

    public function getCircularDesignerTool()
    {
        return $this->_coreRegistry->registry('circulardesignertool');
    }

    public function getPageTitle()
    {
        return $this->getCircularDesignerTool()->getId()?
        __("EDIT CircularDesignerTool '%1'", $this->escapeHtml($this->getCircularDesignerTool()->getName())) :
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
