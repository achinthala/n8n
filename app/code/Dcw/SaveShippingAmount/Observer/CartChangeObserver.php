<?php
namespace Dcw\SaveShippingAmount\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;

class CartChangeObserver implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Execute the observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $this->createLog("Event Firing");
        
        try {
        // Check if "amasty_quote_shipping_amount" is set in session
        if ($this->checkoutSession->getAmastyQuoteShippingAmount()) {
            $this->createLog("Session is set");
                // Unset the session variable
                $this->checkoutSession->unsAmastyQuoteShippingAmount();
            }else{
                $this->createLog("Session is not set");
            }
        } catch (\Exception $e) {
            // Log the exception message
            $this->createLog('Error while unsetting amasty_quote_shipping_amount: ' . $e->getMessage());
        }
        
        
        return true;
    }

    public function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/CartChangeObserver.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }


}
