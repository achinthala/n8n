<?php
namespace Dcw\SplitPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->storeManager = $storeManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->managerInterface = $managerInterface;
        $this->cookieManager = $cookieManager;
        $this->cart = $cart;
        $this->registry = $registry;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }
    
    public function execute()
    {
        /*if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl('checkout/cart');
            return $resultRedirect;
        }*/
        
        if ($this->cart->getQuote()->getItemsCount() == 0) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl('checkout/cart');
            return $resultRedirect;
        }

        if (empty($this->cart->getQuote()->getShippingAddress()->getFirstname())) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl('checkout');
            return $resultRedirect;
        }

        $this->registry->register('tokenbase_method', 'authnetcim');
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
