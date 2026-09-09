<?php
namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddToCartBeforeObserver implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;


    /**
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     */
    public function __construct(\Magento\Checkout\Model\Session $_checkoutSession)
    {
        $this->_checkoutSession = $_checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_checkoutSession->setAddProductTrigger(true);
        $this->_checkoutSession->setAddProductServerSideTrigger(true);
        return $this;
    }
}
