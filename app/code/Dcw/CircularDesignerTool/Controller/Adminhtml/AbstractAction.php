<?php
namespace Dcw\CircularDesignerTool\Controller\Adminhtml;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    protected $_jsHelper;

    protected $_storeManager;

    protected $_resultForwardFactory;

    protected $_resultLayoutFactory;

    protected $_resultPageFactory;

    protected $_resultRedirectFactory;

    protected $_circulardesignertoolFactory;

    protected $_circulardesignertoolCollectionFactory;

    protected $_circulardesignertooltabFactory;

    protected $_circulardesignertooltabCollectionFactory;

    protected $_coreRegistry;

    protected $_fileFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Dcw\CircularDesignerTool\Model\CircularDesignerToolFactory $circulardesignertoolFactory,
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerTool\CollectionFactory
        $circulardesignertoolCollectionFactory,
        \Dcw\CircularDesignerTool\Model\CircularDesignerToolTabFactory $circulardesignertooltabFactory,
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerToolTab\CollectionFactory
        $circulardesignertooltabCollectionFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Image\AdapterFactory $imageAdapter,
        \Magento\Framework\Message\ManagerInterface $messageManager
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
        $this->_circulardesignertoolFactory = $circulardesignertoolFactory;
        $this->_circulardesignertoolCollectionFactory = $circulardesignertoolCollectionFactory;
        $this->_circulardesignertooltabFactory = $circulardesignertooltabFactory;
        $this->_circulardesignertooltabCollectionFactory = $circulardesignertooltabCollectionFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->imageAdapter = $imageAdapter;
        $this->messageManager = $messageManager;
    }
}
