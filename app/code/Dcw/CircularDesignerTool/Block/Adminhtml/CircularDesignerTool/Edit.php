<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml\CircularDesignerTool;

/**
 * CircularDesignerTool block edit form container..
 */

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Dcw_CircularDesignerTool';
        $this->_controller = 'adminhtml_CircularDesignerTool';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save CircularDesignerTool'));
        $this->buttonList->update('delete', 'label', __('Delete'));
        
        $this->buttonList->add(
            'save_and_continue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ],
            ],
            10
        );

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
