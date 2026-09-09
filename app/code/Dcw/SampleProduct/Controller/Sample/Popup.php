<?php
namespace Dcw\SampleProduct\Controller\Sample;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Dcw\SampleProduct\Helper\Data as SampleHelper;

class Popup extends Action
{
    /**
     * @var FormKey
     */
    protected $formKey;
    /**
     * @var Cart
     */
    protected $cart;
    /**
     * @var Product
     */
    protected $product;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var ResultJsonFactory
     */
    private $resultJsonFactory;
    
    /**
     * @var SampleHelper
     */
    private $sampleHelper;
    
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param FormKey $formKey
     * @param Cart $cart
     * @param Product $product
     * @param CheckoutSession $checkoutSession
     * @param ResultJsonFactory $resultJsonFactory
     * @param SampleHelper $sampleHelper
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        FormKey $formKey,
        Cart $cart,
        Product $product,
        CheckoutSession $checkoutSession,
        ResultJsonFactory $resultJsonFactory,
        SampleHelper $sampleHelper
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->product = $product;
        $this->_resultFactory = $resultFactory;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sampleHelper = $sampleHelper;
        parent::__construct($context);
    }

    /**
     * Return pop Html
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $html='';
        if ($this->getRequest()->getParam('pid')) {
            $productid=$this->getRequest()->getParam('pid');
            $postparms=$this->getRequest()->getParams();
            $html=$this->sampleHelper->getTemplate($productid);
        }
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setContents($html);
        return $result;
    }
}
