<?php
declare(strict_types=1);

namespace Dcw\IncstoreShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;

class FinalShippingCheck implements ObserverInterface
{
    public function __construct(
        protected readonly CheckoutSession $checkoutSession
    ) {
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getIncrementId();
        $quoteId = $order->getQuoteId();
        
        $this->createLog("FinalShippingCheck-Start: Order ID: {$orderId}, Quote ID: {$quoteId}, Current Shipping: " . $order->getShippingAmount());
        
        // This is the LAST CHANCE to fix shipping before order is placed
        if ($order->getShippingAmount() == 0) {
            $this->createLog("FinalShippingCheck-CRITICAL: Order ID {$orderId} has ZERO shipping amount - attempting final fix");
            
            // Try to get shipping amount from session data
            $apidata = $this->checkoutSession->getShippingApiDataRaw();
            if (!empty($apidata) && isset($apidata['data']['lineItemShipments'])) {
                $totalShippingAmount = 0;
                $this->createLog("FinalShippingCheck-API-Data: Order ID {$orderId} - Found API data, calculating from lineItemShipments");
                
                foreach ($apidata['data']['lineItemShipments'] as $shipment) {
                    if (isset($shipment['charge'])) {
                        $totalShippingAmount += $shipment['charge'];
                        $this->createLog("FinalShippingCheck-API-Item: Order ID {$orderId} - Item charge: " . $shipment['charge']);
                    }
                }
                
                if ($totalShippingAmount > 0) {
                    $this->createLog("FinalShippingCheck-SUCCESS: Order ID {$orderId} - FINAL FIX from API data: {$totalShippingAmount}");
                    $order->setShippingAmount($totalShippingAmount);
                    $order->setBaseShippingAmount($totalShippingAmount);
                    $order->setGrandTotal($order->getGrandTotal() + $totalShippingAmount);
                    $order->setBaseGrandTotal($order->getBaseGrandTotal() + $totalShippingAmount);
                } else {
                    $this->createLog("FinalShippingCheck-WARNING: Order ID {$orderId} - API data found but total shipping is zero");
                }
            } else {
                $this->createLog("FinalShippingCheck-Fallback: Order ID {$orderId} - No API data, trying order items fallback");
                
                // Fallback: calculate from order items
                $totalShippingAmount = 0;
                $itemsProcessed = 0;
                
                foreach ($order->getAllVisibleItems() as $item) {
                    $itemShipping = (float) $item->getIncstoreItemShipping();
                    if ($itemShipping > 0 && $item->getProductType() == 'simple') {
                        $totalShippingAmount += $itemShipping;
                        $itemsProcessed++;
                        $this->createLog("FinalShippingCheck-Item: Order ID {$orderId} - Item {$item->getSku()} shipping: {$itemShipping}");
                    }
                }
                
                if ($totalShippingAmount > 0) {
                    $this->createLog("FinalShippingCheck-SUCCESS: Order ID {$orderId} - FINAL FIX from order items: {$totalShippingAmount} (processed {$itemsProcessed} items)");
                    $order->setShippingAmount($totalShippingAmount);
                    $order->setBaseShippingAmount($totalShippingAmount);
                    $order->setGrandTotal($order->getGrandTotal() + $totalShippingAmount);
                    $order->setBaseGrandTotal($order->getBaseGrandTotal() + $totalShippingAmount);
                } else {
                    $this->createLog("FinalShippingCheck-CRITICAL-ERROR: Order ID {$orderId}, Quote ID {$quoteId} - NO SHIPPING AMOUNT FOUND! Order will be placed with ZERO shipping!");
                    $this->createLog("FinalShippingCheck-DEBUG: Order ID {$orderId} - Items processed: {$itemsProcessed}, API data available: " . (empty($apidata) ? 'NO' : 'YES'));
                }
            }
        } else {
            $this->createLog("FinalShippingCheck-OK: Order ID {$orderId} - Shipping amount already set: " . $order->getShippingAmount());
        }
        
        $this->createLog("FinalShippingCheck-Complete: Order ID {$orderId} - Final shipping amount: " . $order->getShippingAmount());
    }

    public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/shippingAPI.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
