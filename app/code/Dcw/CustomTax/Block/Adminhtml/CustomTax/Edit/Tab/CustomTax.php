<?php
namespace Dcw\CustomTax\Block\Adminhtml\CustomTax\Edit\Tab;

use Dcw\CustomTax\Model\Status;

/**
 * CustomTax Edit tab.
 */

class CustomTax extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_objectFactory;

    protected $_customtax;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\DataObjectFactory $objectFactory,
        \Dcw\CustomTax\Model\CustomTax $customtax,
        array $data = []
    ) {
        $this->_objectFactory = $objectFactory;
        $this->_customtax = $customtax;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('page.title')->setPageTitle($this->getPageTitle());

        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock(
                'Dcw\CustomTax\Block\Adminhtml\Form\Renderer\Fieldset\Element',
                $this->getNameInLayout().'_fieldset_element'
            )
        );

        return $this;
    }

    protected function _prepareForm()
    {
	$customtaxAttributes = $this->_customtax->getStoreAttributes();
        $customtaxAttributesInStores = ['store_id' => ''];

        foreach ($customtaxAttributes as $customtaxAttribute) {
            $customtaxAttributesInStores[$customtaxAttribute.'_in_store'] = '';
        }

        $dataObj = $this->_objectFactory->create(
            ['data' => $customtaxAttributesInStores]
        );
        $model = $this->_coreRegistry->registry('customtax');

        $dataObj->addData($model->getData());

        $storeViewId = $this->getRequest()->getParam('store');
		
        $form = $this->_formFactory->create();
		
        $form->setHtmlIdPrefix($this->_customtax->getFormFieldHtmlIdPrefix());
		
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Import Custom Tax')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $elements = [];
    
	
	$elements['sheet_file'] =  $fieldset->addField(
            'sheet_file',
            'file',
            ['name' => 'sheet_file',
	     'label' => __('Sheet File'),
	     'title' => __('Sheet File'),
	     'required' => false,
	    ]
        );
		
       

        $form->addValues($dataObj->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getCustomTax()
    {
        return $this->_coreRegistry->registry('customtax');
    }

    public function getPageTitle()
    {
        return $this->getCustomTax()->getId()? __("EDIT CustomTax '%1'", $this->escapeHtml($this->getCustomTax()->getName())) : __('Import Custom Tax');
    }

    public function getTabLabel()
    {
        return __('Import Custom Tax');
    }

    public function getTabTitle()
    {
        return __('Import Custom Tax');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    /*public function getFormHtml()
    {
       // get the current form as html content.
        $html = parent::getFormHtml();
        //Append the phtml file after the form content.
        $html .= $this->setTemplate('Dcw_CustomTax::form/import_custom_tax.phtml')->toHtml(); 
        return $html;
    }*/
}
