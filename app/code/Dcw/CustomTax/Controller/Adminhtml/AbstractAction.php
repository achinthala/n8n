<?php
namespace Dcw\CustomTax\Controller\Adminhtml;

use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    protected $_jsHelper;

    protected $_storeManager;

    protected $_resultForwardFactory;

    protected $_resultLayoutFactory;

    protected $_resultPageFactory;

    protected $_resultRedirectFactory;

    protected $_customtaxFactory;

    protected $_customtaxCollectionFactory;

    protected $_coreRegistry;

    protected $_fileFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dcw\CustomTax\Model\CustomTaxFactory $customtaxFactory,
        \Dcw\CustomTax\Model\ResourceModel\CustomTax\CollectionFactory $customtaxCollectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\Region $region,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Tax\Model\Calculation $calculation
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_fileFactory = $fileFactory;
        $this->_storeManager = $storeManager;
        $this->_jsHelper = $jsHelper;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultLayoutFactory = $resultLayoutFactory;
        $this->_resultForwardFactory = $resultForwardFactory;
        $this->_resultRedirectFactory = $context->getResultRedirectFactory();
        $this->_customtaxFactory = $customtaxFactory;
        $this->_customtaxCollectionFactory = $customtaxCollectionFactory;
        $this->_csv = $csv;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->regionFactory = $regionFactory;
        $this->region = $region;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->objectManager = $objectManager;
        $this->_calculation = $calculation;
    }
}
