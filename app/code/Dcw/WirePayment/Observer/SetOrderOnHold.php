<?php
namespace Dcw\WirePayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class SetOrderOnHold implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getPayment()->getMethod() == 'wirepayment') {
            $order->hold();
            $order->save();
        }
    }
}
