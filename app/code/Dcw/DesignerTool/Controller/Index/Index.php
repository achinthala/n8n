<?php

namespace Dcw\DesignerTool\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
    ) {
        $this->_productloader = $_productloader;
        $this->storeManager = $storeManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->managerInterface = $managerInterface;
        $this->cookieManager = $cookieManager;
        parent::__construct($context);
    }
    
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        $product_id = $this->getRequest()->getParam('id');
        $_product = $this->_productloader->create()->load($this->getRequest()->getParam('id'));

        if (empty($product_id) || empty($_product->getId()) || $_product->getTypeId()!='configurable') {
            $resultRedirect->setUrl($this->storeManager->getStore()->getBaseUrl());
            return $resultRedirect;
        }
        
        $designer_tool_id = $this->cookieManager->getCookie('design_tool_id');
        if (empty($designer_tool_id)) {
            $resultRedirect->setUrl($_product->getProductUrl());
            return $resultRedirect;
        }

        $designer_tool_idArr = explode("_", $designer_tool_id);
        $child_id = $designer_tool_idArr[1];

        $_children = $_product->getTypeInstance()->getUsedProducts($_product);
        $child_product_ids = [];
        foreach ($_children as $_child_product) {
            $child_product_ids[] = $_child_product->getId();
        }
        $_child = $this->_productloader->create()->load($child_id);

        if ((!empty($product_id) || !empty($_product->getId())) &&
        (empty($child_id) || empty($_child->getId())) ||
        !in_array($_child->getId(), $child_product_ids)) {
            $resultRedirect->setUrl($_product->getProductUrl());
            return $resultRedirect;
        }

        if (empty($_child->getIncstoresPimExactWidthInches()) || empty($_child->getIncstoresPimExactLengthInches())) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            $this->managerInterface->addError(__("Width or Length is missing for custom design."));
            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__("Flooring designer"));
        return $resultPage;
    }
}
