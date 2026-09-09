<?php
 
namespace Dcw\Checkout\Controller\Checkout;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteRepository;
 
class saveInQuote extends Action
{
    protected $resultForwardFactory;
    protected $layoutFactory;
    protected $cart;
 
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        LayoutFactory $layoutFactory,
        Cart $cart,
        Session $checkoutSession,
        QuoteRepository $quoteRepository
    )
    {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->layoutFactory = $layoutFactory;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
 
        parent::__construct($context);
    }
 
    public function execute()
    {
		$loadingdock = $this->getRequest()->getParam('loading_dock');
		if($this->getRequest()->getParam('loading_dock')){
			$checkedval = $this->getRequest()->getParam('loading_dock');
			$radiocheck = "loading_dock";
		} else {
			$checkedval = $this->getRequest()->getParam('resident_address');
			$radiocheck = "resident_address";
		}
        $quoteId = $this->checkoutSession->getQuoteId();
        $quote = $this->quoteRepository->get($quoteId);
		if($radiocheck == "loading_dock" && $checkedval!=""){
			$quote->setLoadingDock($checkedval);
		} else {
			$quote->setResidentialAddress($checkedval);
		}
		
        $quote->save();
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/quotes.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
		$logger->info($quoteId);
		$logger->info($radiocheck.'--'.$checkedval);
    }
}