<?php

namespace Dcw\RequestQuote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveCustomQuoteItemData implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getData('item');
    
        $customValue = $quoteItem->getCustomPrice();
     
        $baseValue = $quoteItem->getBasePrice();
        // If custom data exists, set it back before saving to prevent resetting
        if ($customValue && !$quoteItem->getOriginalQuoteItemPrice()) {
            $quoteItem->setOriginalQuoteItemPrice($customValue);
        } else if($baseValue && !$quoteItem->getOriginalQuoteItemPrice()){
            $quoteItem->setOriginalQuoteItemPrice($baseValue);
        }
    }
}
