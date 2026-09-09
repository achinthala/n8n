<?php

namespace Dcw\CustomTax\Controller\Adminhtml;

abstract class CustomTax extends \Dcw\CustomTax\Controller\Adminhtml\AbstractAction
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dcw_CustomTax::customtax_customtax');
    }
}
