<?php

namespace Dcw\CircularDesignerTool\Helper;

/**
 * Helper Data
 */

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_backendUrl;

    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerToolTab\CollectionFactory
        $circulardesignertooltabCollectionFactory
    ) {
        parent::__construct($context);
        $this->_backendUrl = $backendUrl;
        $this->_storeManager = $storeManager;
        $this->_circulardesignertooltabCollectionFactory = $circulardesignertooltabCollectionFactory;
    }

    public function getBackendUrl($route = '', $params = ['_current' => true])
    {
        return $this->_backendUrl->getUrl($route, $params);
    }

    public function getIdFromTabName($tab_name)
    {
        $circulardesignertooltabCollection = $this->_circulardesignertooltabCollectionFactory->create();
        $circulardesignertooltabCollection->addFieldTOFilter('cdt_tab_title', $tab_name);
        $collection = $circulardesignertooltabCollection->getFirstItem()->getData();

        return $collection['cdt_tab_id'];
    }
}
