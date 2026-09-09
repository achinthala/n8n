<?php
namespace Dcw\CustomTax\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Dcw\CustomTax\Model\CustomTaxFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    
    protected $_modelCustomTaxFactory;
    
    public function __construct(
        Context $context,
        CustomTaxFactory $modelCustomTaxFactory
    ) {
        parent::__construct($context);
        $this->_modelCustomTaxFactory = $modelCustomTaxFactory;
    }
    
    public function execute()
    {
        $this->_view->loadLayout();
           $this->_view->renderLayout();
    }
}
