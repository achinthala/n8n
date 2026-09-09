<?php

namespace Dcw\CustomTax\Controller\Adminhtml\CustomTax;

/**
 * Grid action.
 */

class Grid extends \Dcw\CustomTax\Controller\Adminhtml\CustomTax
{
    public function execute()
    {
    
        $resultLayout = $this->_resultLayoutFactory->create();
        return $resultLayout;
    }
}
