<?php
declare(strict_types=1);

namespace Dcw\IncstoreShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;

class PreserveShippingAmount implements ObserverInterface
{
    public function __construct(
        protected readonly CheckoutSession $checkoutSession
    ) {
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        $orderId = $order->getIncrementId();
        $quoteId = $quote->getId();
        
        $this->createLog("PreserveShippingAmount-Start: Order ID: {$orderId}, Quote ID: {$quoteId}");
        
        // Get shipping amount from quote
        $quoteShippingAmount = $quote->getShippingAddress()->getShippingAmount();
        $orderShippingAmount = $order->getShippingAmount();
        
        $this->createLog("PreserveShippingAmount-Data: Order ID {$orderId} - Quote Shipping: {$quoteShippingAmount}, Order Shipping: {$orderShippingAmount}");
        
        // If quote has shipping amount but order doesn't, copy it
        if ($quoteShippingAmount > 0 && $orderShippingAmount == 0) {
            $this->createLog("PreserveShippingAmount-Copy: Order ID {$orderId} - Copying shipping amount from quote: {$quoteShippingAmount}");
            $order->setShippingAmount($quoteShippingAmount);
            $order->setBaseShippingAmount($quote->getShippingAddress()->getBaseShippingAmount());
            $this->createLog("PreserveShippingAmount-SUCCESS: Order ID {$orderId} - Successfully copied shipping amount from quote");
        } else if ($quoteShippingAmount > 0 && $orderShippingAmount > 0) {
            $this->createLog("PreserveShippingAmount-OK: Order ID {$orderId} - Both quote and order have shipping amounts, no action needed");
        } else if ($quoteShippingAmount == 0 && $orderShippingAmount == 0) {
            $this->createLog("PreserveShippingAmount-WARNING: Order ID {$orderId} - Both quote and order have zero shipping, trying session fallback");
        }
        
        // Also try to get from session data as backup
        if ($order->getShippingAmount() == 0) {
            $this->createLog("PreserveShippingAmount-Fallback: Order ID {$orderId} - Order shipping is zero, checking session data");
            
            $apidata = $this->checkoutSession->getShippingApiDataRaw();
            if (!empty($apidata) && isset($apidata['data']['lineItemShipments'])) {
                $totalShippingAmount = 0;
                $itemsProcessed = 0;
                
                $this->createLog("PreserveShippingAmount-API-Data: Order ID {$orderId} - Found API data, calculating from lineItemShipments");
                
                foreach ($apidata['data']['lineItemShipments'] as $shipment) {
                    if (isset($shipment['charge'])) {
                        $totalShippingAmount += $shipment['charge'];
                        $itemsProcessed++;
                        $this->createLog("PreserveShippingAmount-API-Item: Order ID {$orderId} - Item charge: " . $shipment['charge']);
                    }
                }
                
                if ($totalShippingAmount > 0) {
                    $this->createLog("PreserveShippingAmount-SUCCESS: Order ID {$orderId} - Setting shipping amount from API data: {$totalShippingAmount} (processed {$itemsProcessed} items)");
                    $order->setShippingAmount($totalShippingAmount);
                    $order->setBaseShippingAmount($totalShippingAmount);
                    $order->setGrandTotal($order->getGrandTotal() + $totalShippingAmount);
                    $order->setBaseGrandTotal($order->getBaseGrandTotal() + $totalShippingAmount);
                } else {
                    $this->createLog("PreserveShippingAmount-WARNING: Order ID {$orderId} - API data found but total shipping is zero");
                }
            } else {
                $this->createLog("PreserveShippingAmount-No-API: Order ID {$orderId} - No API data available in session");
            }
        }
        
        $finalShippingAmount = $order->getShippingAmount();
        $this->createLog("PreserveShippingAmount-Complete: Order ID {$orderId} - Final order shipping amount: {$finalShippingAmount}");
        
        if ($finalShippingAmount == 0) {
            $this->createLog("PreserveShippingAmount-CRITICAL: Order ID {$orderId} - Order will be converted with ZERO shipping amount!");
        }
    }

    public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/shippingAPI.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
