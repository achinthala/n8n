<?php
namespace Dcw\Checkout\Block;

class Index extends \Magento\Framework\View\Element\Template
{
    protected $coreRegistry;
    protected $scopeConfig;
    protected $storeManager;
    protected $orderAddressRenderer;
    protected $request;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\Address\Renderer $orderAddressRenderer,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->orderAddressRenderer = $orderAddressRenderer;
        $this->request = $request;
        parent::__construct($context, $data);
    }
    
    public function orderAddressRenderer()
    {
        return $this->orderAddressRenderer;
    }

    public function getFullActionName()
    {
        return $this->request->getFullActionName();
    }

}
