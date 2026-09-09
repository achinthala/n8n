<?php
namespace Dcw\CustomTax\Controller\Adminhtml\CustomTax;

/**
 * New CustomTax action.
 */

class NewAction extends \Dcw\CustomTax\Controller\Adminhtml\CustomTax
{
    public function execute()
    {
        $resultForward = $this->_resultForwardFactory->create();

        return $resultForward->forward('edit');
    }
}
