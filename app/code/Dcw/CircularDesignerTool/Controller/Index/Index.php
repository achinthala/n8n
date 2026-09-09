<?php
namespace Dcw\CircularDesignerTool\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Dcw\CircularDesignerTool\Model\CircularDesignerToolFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    
    protected $_modelCircularDesignerToolFactory;
    
    public function __construct(
        Context $context,
        CircularDesignerToolFactory $modelCircularDesignerToolFactory
    ) {
        parent::__construct($context);
        $this->_modelCircularDesignerToolFactory = $modelCircularDesignerToolFactory;
    }
    
    public function execute()
    {
        $this->_view->loadLayout();
           $this->_view->renderLayout();
    }
}
