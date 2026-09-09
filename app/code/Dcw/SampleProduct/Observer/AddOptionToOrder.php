<?php
namespace Dcw\SampleProduct\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;

class AddOptionToOrder implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $quote = $observer->getQuote();
            $order = $observer->getOrder();
            $quoteItems = [];
            // Map Quote Item with Quote Item Id
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                $quoteItems[$quoteItem->getId()] = $quoteItem;
            }
            foreach ($order->getAllVisibleItems() as $orderItem) {
                $quoteItemId = $orderItem->getQuoteItemId();
                $quoteItem = $quoteItems[$quoteItemId];
                $additionalOptions = $quoteItem->getOptionByCode('additional_options');
                if($additionalOptions) {
                    if ($additionalOptions->getValue()) {
                        // Get Order Item's other options
                        $options = $orderItem->getProductOptions();
                        $customOptions=json_decode($additionalOptions->getValue(),1);
                        if($customOptions){
                            $name=$customOptions["configurable_product_name"]['value'];
                            if($name!="") {
                                $orderItem->setName($name);
                            }
                            //unset($customOptions['configurable_product_id']);
                            //unset($customOptions['configurable_product_url']);
                            //unset($customOptions['configurable_product_name']);
                            //unset($customOptions['configurable_product_image']);
                        }
                        // Set additional options to Order Item
                        $options['additional_options'] = $customOptions;
                        $orderItem->setProductOptions($options);
                    }
                }   
            }
        } catch (\Exception $e) {
            // catch error if any
        }
    }
}