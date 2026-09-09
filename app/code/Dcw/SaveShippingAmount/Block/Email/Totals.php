<?php

namespace Dcw\SaveShippingAmount\Block\Email;

use Amasty\RequestQuote\Block\Email\Totals as AmastyTotals;
use Magento\Quote\Model\QuoteFactory;

class Totals extends \Amasty\RequestQuote\Block\Email\Totals
{
    /**
     * Override methods or add custom logic here
     */

     private $allowedCodes = [
        'subtotal',
        'weee_tax',
        'tax',
        'shipping',
        'grand_total'
    ];

     public function getTotals()
    {
        $totals = parent::getTotals();
        $this->createLog(json_encode($totals));

        //==============Modify the amount of email==================
        $quoteId =  $this->getData('quote_id');
        $this->createLog("quoteid = ".$quoteId);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $quoteFactory = $objectManager->get(QuoteFactory::class);

        $quote = $quoteFactory->create()->load($quoteId);

        // Get the table name
        $amastyQuoteTable = $resource->getTableName('amasty_quote');
        $connection = $resource->getConnection();
        $sqlSelect = "SELECT * FROM $amastyQuoteTable WHERE quote_id = :quoteId LIMIT 1";
        $bind = ['quoteId' => $quoteId];
        $result = $connection->fetchRow($sqlSelect, $bind);

        $additionalTax = 0;
        $additionalShipping = 0;

        $grandTotal=0;
        if ($result) {
            $additionalTax = $result['tax_amount'] ?? 0;
            $additionalShipping = $result['shipping_amount'] ?? 0;

            $this->createLog("additionalTax = ".$additionalTax);
            $this->createLog("additionalShipping = ".$additionalShipping);
        }
        //==============End of Modify the  amount of email==========
        foreach ($totals as $code => $total) {
            if (!in_array($code, $this->allowedCodes)) {
                unset($totals[$code]);
                continue;
            }
            $this->createLog($total['title']." = ".$total['value']);
            
            if ($code == 'shipping') {
                $total['value'] = $quote->getShippingAddress()->getBaseShippingAmount();
            } 
            else if ($code == 'tax') {
                $total['value'] = $quote->getShippingAddress()->getBaseTaxAmount();
            }
           
            if ($code != 'grand_total') {
                $grandTotal += $total['value'];
            }else{
                $this->createLog("title is grand total ".$grandTotal);
                $total['value'] = $grandTotal;
            }

            $total['label'] = $total['title'].' ';
            
            $this->createLog($total['title']." = ".$total['value']);
        }
        
        return $totals;
    }

    public function createLog($message){
        // $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/requestQuoteEMail.log');
         $fileName = date('Y-m-d').'requestQuoteEMail.log';
         $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/' .$fileName);
         $logger = new \Zend_Log();
         $logger->addWriter($writer);
         return $logger->info($message);
     }
}
