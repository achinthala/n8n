<?php

namespace Dcw\SaveShippingAmount\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Address;

class SetFixedShipping implements ObserverInterface
{
    protected $addressFactory;

    public function __construct(AddressFactory $addressFactory)
    {
        $this->addressFactory = $addressFactory;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $quoteId = $quote->getId();
        $this->createLog("quote id ".$quoteId);
        // Fetch the shipping address data from the quote_address table
        $addressCollection = $this->addressFactory->create()->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('address_type', 'shipping')
            ->setPageSize(1);

       // Check if an address exists for the current quote
       if ($addressCollection->getSize() > 0) {
        $this->createLog("Address factory found ");
            $addressData = $addressCollection->getFirstItem();

            // Retrieve the existing shipping amount and tax amount
            $shippingAmount = $addressData->getShippingAmount();
            $baseShippingAmount = $addressData->getBaseShippingAmount();
            $shippingTaxAmount = $addressData->getShippingTaxAmount();

            $taxAmount = $addressData->getTaxAmount();
            $baseTaxAmount = $addressData->getBaseTaxAmount();


            $this->createLog("Address Shipping AMount= ".$shippingAmount);
            $this->createLog("Address Tax AMount= ".$taxAmount);
            // Check if a shipping amount is already set in the database
            if ($shippingAmount && $taxAmount) {
                // Set the shipping amount and tax amount from the database
               // if($shippingAmount==0){
                    $shippingAddress->setShippingAmount(500);
                    $shippingAddress->setBaseShippingAmount(500);

                     // Optionally set a custom shipping method and description
                    $shippingAddress->setShippingMethod('flatrate_flatrate'); // Your method code
                    $shippingAddress->setShippingDescription('Fixed Shipping Rate');
                    
                // }else{
                //     $shippingAddress->setCollectShippingRates(true);
                // }
               
                if($taxAmount!=0){
                    // Set the tax amounts from the database
                    $shippingAddress->setTaxAmount($taxAmount);
                    $shippingAddress->setBaseTaxAmount($baseTaxAmount);
                }
               
                $this->createLog("amount are set ");
                $this->createLog("::::::::::::::::::::::::::::::::::::::::::::::::::::::");
                // Prevent Magento from calculating shipping rates again
                $shippingAddress->setCollectShippingRates(false); // Prevent recalculation
                // Re-collect totals to ensure the custom values are applied
                
                $shippingAddress->save();
               
            } else {
                // If no valid shipping amount is found, allow Magento to calculate shipping rates as usual
                $shippingAddress->setCollectShippingRates(true);
            }
        } else {
            // If no shipping address is found, allow Magento to calculate shipping rates as usual
            $shippingAddress->setCollectShippingRates(true);
        }
    }

    function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/FixeShipping.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
