<?php
namespace Dcw\SampleProduct\Controller\Sample;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Customer\Model\Visitor;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\CustomerData\CompareProducts;

class CompareList extends Action
{
    /**
     * @var FormKey
     */
    protected $formKey;
    /**
     * @var Product
     */
    protected $product;
   
    /**
     * @var ResultJsonFactory
     */
    private $resultJsonFactory;
    
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

     /**
     * @var Visitor
     */
    protected $_customerVisitor;

     /**
     * @var CustomerSession
     */
    protected $_customerSession;

     /**
     * @var CompareProducts
     */
    protected $listCompare;


    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param FormKey $formKey
     * @param Product $product
     * @param ResultJsonFactory $resultJsonFactory
     * @param Visitor $_customerVisitor
     * @param CustomerSession $_customerSession
     * @param CompareProducts $listCompare
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        FormKey $formKey,
        Product $product,
        ResultJsonFactory $resultJsonFactory,
        Visitor $_customerVisitor,
        CustomerSession $_customerSession,
        CompareProducts $listCompare
    ) {
        $this->formKey = $formKey;
        $this->product = $product;
        $this->_resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_customerVisitor = $_customerVisitor;
        $this->_customerSession = $_customerSession;
        $this->listCompare = $listCompare;
        parent::__construct($context);
    }

    /**
     * Sample Product add to cart
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {   
        $sampleproducts=array();
        $comparelist=$this->listCompare->getSectionData();
        if ($comparelist && isset ($comparelist['items'])) {
            foreach ($comparelist['items'] as $item){
                $sampleproducts[]=$item['id'];
            }

        }
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($sampleproducts);
        return $resultJson;
        print_r($sampleproducts);die;
        echo 'eeee'.$this->_customerVisitor->getId();die; 
    }
}
