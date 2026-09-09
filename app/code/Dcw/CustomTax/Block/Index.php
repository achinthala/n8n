<?php
namespace Dcw\CustomTax\Block;

use Dcw\CustomTax\Model\Status;

/**
 *  CustomTax Block
 */

class Index extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    
    protected $_customtaxCollectionFactory;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Dcw\CustomTax\Model\ResourceModel\CustomTax\CollectionFactory $customtaxCollectionFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_customtaxCollectionFactory = $customtaxCollectionFactory;
        parent::__construct($context, $data);
    }
    /**
     * Preparing global layout
     *
     * @return $this
     */
    public function _prepareLayout()
    {
        $this->_addBreadcrumbs();
        $this->pageConfig->getTitle()->set('CustomTax');
        $this->pageConfig->setKeywords('');
        $this->pageConfig->setDescription('');
            
        return parent::_prepareLayout();
    }
    
    protected function _addBreadcrumbs()
    {
        if (($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs'))
        ) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            );
            $breadcrumbsBlock->addCrumb(
                'customtax',
                [
                'label' => __('CustomTax'),
                ]
            );
        }
    }
    
    public function getCustomTax()
    {
        
        $customtaxCollection = $this->_customtaxCollectionFactory->create();
        $customtaxCollection->addFieldToFilter('status', 1);
        $collection = $customtaxCollection->getData();
        return $collection;
    }
}
