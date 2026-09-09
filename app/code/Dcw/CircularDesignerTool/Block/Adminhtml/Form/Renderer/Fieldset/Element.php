<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml\Form\Renderer\Fieldset;

class Element extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element
{
    protected $_template = 'Dcw_CircularDesignerTool::form/renderer/fieldset/element.phtml';

    public function getElementName()
    {
        return $this->getElement()->getName();
    }

    public function getElementStoreViewId()
    {
        return $this->getElement()->getStoreViewId();
    }

    public function canDisplayUseDefault()
    {
        return ($this->getRequest()->getParam('store') &&
        $this->getElement()->getDateFormat() == null &&
        $this->getElementName() != 'id') ? true : false;
    }

    public function usedDefault()
    {
        return $this->getElementStoreViewId() ? false : true;
    }

    public function checkFieldDisable()
    {
        if (!$this->getElementStoreViewId() && $this->getElementName() != 'id' &&
        $this->canDisplayUseDefault() && $this->usedDefault()) {
            $this->getElement()->setDisabled(true);
        }

        return $this;
    }

    public function getScopeLabel()
    {
        if ($this->getElement()->getDateFormat() != null || $this->getElementName() == 'id') {
            return '[GLOBAL]';
        }

        return '[STORE VIEW]';
    }

    public function getElementLabelHtml()
    {
        $element = $this->getElement();
        $label = $element->getLabel();
        if (!empty($label)) {
            $element->setLabel(__($label));
        }

        return $element->getLabelHtml();
    }

    public function getElementHtml()
    {
        return $this->getElement()->getElementHtml();
    }

    protected function _getDefaultStoreId()
    {
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }
}
