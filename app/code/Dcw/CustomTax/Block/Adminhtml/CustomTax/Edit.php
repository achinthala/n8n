<?php
namespace Dcw\CustomTax\Block\Adminhtml\CustomTax;

/**
 * CustomTax block edit form container..
 */

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Dcw_CustomTax';
        $this->_controller = 'Adminhtml_CustomTax';

        parent::_construct();

        $this->buttonList->remove('delete');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('back');

        $this->buttonList->update('save', 'label', __('Import Custom Tax'));

        if ($this->getRequest()->getParam('saveandclose')) {
            $this->_formScripts[] = 'window.close();';
        }
    }

    protected function getSaveAndContinueUrl()
    {
        return $this->getUrl(
            '*/*/save',
            [
                '_current' => true,
                'back' => 'edit',
                'tab' => '{{tab_id}}',
                'store' => $this->getRequest()->getParam('store'),
                'id' => $this->getRequest()->getParam('id'),
            ]
        );
    }

    protected function getSaveAndCloseWindowUrl()
    {
        return $this->getUrl(
            '*/*/save',
            [
                '_current' => true,
                'back' => 'edit',
                'tab' => '{{tab_id}}',
                'store' => $this->getRequest()->getParam('store'),
                'id' => $this->getRequest()->getParam('id'),
                'saveandclose' => 1,
            ]
        );
    }
}
