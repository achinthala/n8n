<?php
namespace Dcw\CompareRestriction\Block;

class Index extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Model\Session  $customerSession,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->_registry = $registry;
        $this->_catalogSession = $catalogSession;
        $this->_coreSession = $coreSession;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }
    
    public function getCurrentCatId()
    {
        $current_category_id = '';
        if ($this->_registry->registry('current_category')) {
            $current_category = $this->_registry->registry('current_category');
            $current_category_id = $current_category->getId();
        }
        return $current_category_id;
    }
        
    public function getCatalogSession()
    {
        return $this->_catalogSession;
    }

    public function getCoreSession() 
    {
        return $this->_coreSession;
    }

    public function getCustomerSession() 
    {
        return $this->_customerSession;
    }
    
   
}
