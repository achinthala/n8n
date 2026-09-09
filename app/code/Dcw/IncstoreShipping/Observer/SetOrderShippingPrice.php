<?php
declare(strict_types=1);

namespace Dcw\IncstoreShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;

class SetOrderShippingPrice implements ObserverInterface
{
    public function __construct(
        protected readonly CheckoutSession $checkoutSession
    ) {
    }
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        
        // At this stage, order might not have Order ID yet, so use Quote ID as primary identifier
        $quoteId = $quote ? $quote->getId() : ($order ? $order->getQuoteId() : 'UNKNOWN');
        $orderId = $order ? $order->getIncrementId() : 'PENDING';
        $orderEntityId = $order ? $order->getId() : 'PENDING';

        $this->createLog("SetOrderShippingPrice-Start: Quote ID: {$quoteId}, Order ID: {$orderId}, Order Entity ID: {$orderEntityId}");
        $this->createLog("SetOrderShippingPrice-Data: Quote ID {$quoteId} - Current Order Shipping: " . ($order ? $order->getShippingAmount() : 'N/A'));

        $apidata = $this->checkoutSession->getShippingApiDataRaw();
        $this->createLog("SetOrderShippingPrice-Session: Quote ID {$quoteId} - Session API Data: " . json_encode($apidata));

        // CHECK ADDRESS DATA at the START of observer
        if ($quote && $quote->getShippingAddress()) {
            $initialAddress = $quote->getShippingAddress();
            $initialFirstname = $initialAddress->getFirstname();
            $initialLastname = $initialAddress->getLastname();
            $this->createLog("SetOrderShippingPrice-Initial-Address: Quote ID {$quoteId} - Initial Name: '{$initialFirstname}' '{$initialLastname}'");
            
            if (empty($initialFirstname) || empty($initialLastname)) {
                $this->createLog("SetOrderShippingPrice-CRITICAL-WARNING: Quote ID {$quoteId} - Address ALREADY has empty firstname/lastname at START of observer!");
            }
        }

        //If session shipping API Data null collect the shipping price from the quote line item & set shipping amount
        if (empty($this->checkoutSession->getShippingApiDataRaw())) {
            $this->createLog("SetOrderShippingPrice-Null-Session: Quote ID {$quoteId} - Session API Data is null, calculating from quote items");

            if ($quote) {
                $totalShippingAmount = 0;
                $itemsProcessed = 0;

                $this->createLog("SetOrderShippingPrice-Items: Quote ID {$quoteId} - Processing quote items for shipping calculation");

                foreach ($quote->getAllVisibleItems() as $item) {
                    $itemShipping = (float) $item->getIncstoreItemShipping();
                    $totalShippingAmount += $itemShipping;
                    $itemsProcessed++;
                    
                    $this->createLog("SetOrderShippingPrice-Item: Quote ID {$quoteId} - Item {$item->getSku()} shipping: {$itemShipping}");
                }

                $this->createLog("SetOrderShippingPrice-Calculation: Quote ID {$quoteId} - Total calculated shipping: {$totalShippingAmount} (processed {$itemsProcessed} items)");

                $shippingAddress = $quote->getShippingAddress();
				$methodCode = $shippingAddress ? $shippingAddress->getShippingMethod() : null;
				$this->createLog("SetOrderShippingPrice-QuoteShippingMethod: Quote ID: {$quoteId}, Order ID: {$orderId}, methodCode: {$methodCode}");
				
				if($totalShippingAmount == 0){
					$this->createLog("SetOrderShippingPrice-quote-item: Quote ID {$quoteId} - total item shipping is zero");
					$orderShipping = $order ? $order->getShippingAmount() : 0;
					if($orderShipping){
						$this->createLog("SetOrderShippingPrice: Quote ID {$quoteId} - Order Shipping: " . ($order ? $order->getShippingAmount() : 'N/A'));
						$totalShippingAmount = $orderShipping;
					}
				}elseif ($methodCode && $methodCode !== 'incstoreshipping_incstoreshipping') {
					$orderShipping = $order ? $order->getShippingAmount() : 0;
					if($orderShipping){
						$this->createLog("SetOrderShippingPrice lineItemHaveShippingPrice: Quote ID {$quoteId} - Order Shipping: " . ($order ? $order->getShippingAmount() : 'N/A'));
						$totalShippingAmount = $orderShipping;
					}
				}

                if ($shippingAddress) {
                    $this->createLog("SetOrderShippingPrice-Quote-Address: Quote ID {$quoteId} - Setting shipping amount on quote address: {$totalShippingAmount}");
                    $shippingAddress->setShippingAmount($totalShippingAmount);
                    $shippingAddress->setBaseShippingAmount($totalShippingAmount);

                    // Prevent shipping rate recollection
                    $shippingAddress->setCollectShippingRates(false);
                    $this->createLog("SetOrderShippingPrice-Collect-Disabled: Quote ID {$quoteId} - Disabled shipping rate recollection");

                    // IMPORTANT: DO NOT call collectTotals() during order submission!
                    // collectTotals() can reload the quote from DB and lose in-memory address data
                    // Instead, just mark that totals need recalculation - Magento core will handle it
                    $quote->setTotalsCollectedFlag(false);
                    $this->createLog("SetOrderShippingPrice-Totals-Flag-Reset: Quote ID {$quoteId} - Marked for totals recalculation");
                } else {
                    $this->createLog("SetOrderShippingPrice-ERROR: Quote ID {$quoteId} - Quote shipping address not found");
                }

                // ALSO set the shipping amount directly on the ORDER object
                if ($order) {
                    $this->createLog("SetOrderShippingPrice-Order-Set: Quote ID {$quoteId} - Setting shipping amount on order object: {$totalShippingAmount}");
                    $order->setShippingAmount($totalShippingAmount);
                    $order->setBaseShippingAmount($totalShippingAmount);
                    $finalOrderShipping = $order->getShippingAmount();
                    $this->createLog("SetOrderShippingPrice-SUCCESS: Quote ID {$quoteId} - Order shipping amount set to: {$finalOrderShipping}");
                    
                    if ($finalOrderShipping == 0) {
                        $this->createLog("SetOrderShippingPrice-CRITICAL: Quote ID {$quoteId} - Order shipping amount is still ZERO after calculation!");
                    }
                } else {
                    $this->createLog("SetOrderShippingPrice-WARNING: Quote ID {$quoteId} - Order object not available yet");
                }
            } else {
                $this->createLog("SetOrderShippingPrice-ERROR: Quote ID {$quoteId} - Quote not found in observer");
            }
        } else {
            $this->createLog("SetOrderShippingPrice-Session-OK: Quote ID {$quoteId} - Session API data available, no action needed");
        }

        $finalOrderShipping = $order ? $order->getShippingAmount() : 'N/A';
        $this->createLog("SetOrderShippingPrice-Complete: Quote ID {$quoteId} - Final order shipping amount: {$finalOrderShipping}");
    }

    public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/shippingAPI.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
