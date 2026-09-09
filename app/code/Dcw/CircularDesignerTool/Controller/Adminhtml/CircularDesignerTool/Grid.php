<?php

namespace Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool;

/**
 * Grid action.
 */

class Grid extends \Dcw\CircularDesignerTool\Controller\Adminhtml\CircularDesignerTool
{
    public function execute()
    {
    
        $resultLayout = $this->_resultLayoutFactory->create();
        return $resultLayout;
    }
}
