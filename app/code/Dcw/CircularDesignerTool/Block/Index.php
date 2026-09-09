<?php
namespace Dcw\CircularDesignerTool\Block;

use Dcw\CircularDesignerTool\Model\Status;

/**
 *  CircularDesignerTool Block
 */

class Index extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    
    protected $_circulardesignertoolCollectionFactory;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerToolTab\CollectionFactory
        $circulardesignertooltabCollectionFactory,
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerTool\CollectionFactory
        $circulardesignertoolCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dcw\CircularDesignerTool\Helper\Data $circularDesignerHelper,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_circulardesignertooltabCollectionFactory = $circulardesignertooltabCollectionFactory;
        $this->_circulardesignertoolCollectionFactory = $circulardesignertoolCollectionFactory;
        $this->storeManager = $storeManager;
        $this->circularDesignerHelper = $circularDesignerHelper;
        $this->_json = $json;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
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
        $this->pageConfig->getTitle()->set('CircularDesignerTool');
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
                'link' => $this->storeManager->getStore()->getBaseUrl()
                ]
            );
            $breadcrumbsBlock->addCrumb(
                'circulardesignertool',
                [
                'label' => __('CircularDesignerTool'),
                ]
            );
        }
    }

    public function getMatSize($id)
    {
        $circular_designer_tool_table = $this->connection->getTableName("circular_designer_tool");
        $circular_designer_tool_table_sql = $this->connection->select()
        ->from($circular_designer_tool_table, 'raw_designer_data')->where('id = ?', $id);
        $raw_designer_data = $this->connection->fetchOne($circular_designer_tool_table_sql);
        $raw_designer_data_arr = $this->_json->unserialize($raw_designer_data);
       
        return $raw_designer_data_arr['mat']['size'];
    }

    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
}
