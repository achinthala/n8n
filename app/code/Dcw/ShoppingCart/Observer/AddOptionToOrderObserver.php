<?php 
namespace Dcw\ShoppingCart\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
 
class AddOptionToOrderObserver implements ObserverInterface
{
    private $serializer;
 
    public function __construct(
        SerializerInterface $serializer
    )
    {
        $this->serializer = $serializer;
    }
 
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
            
		try {
            $quote = $observer->getQuote();
            $order = $observer->getOrder();
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                $quoteItems[$quoteItem->getId()] = $quoteItem;
            }
 
            foreach ($order->getAllVisibleItems() as $orderItem) {
                $quoteItemId = $orderItem->getQuoteItemId();
                $quoteItem = $quoteItems[$quoteItemId];
                $additionalOptions = $quoteItem->getOptionByCode('additional_options');
				if($additionalOptions) {
					if ($additionalOptions->getValue()) {
						$options = $orderItem->getProductOptions();
						$options['additional_options'] = $this->serializer->unserialize($additionalOptions->getValue());
						$orderItem->setProductOptions($options);
					}
				}
			}
		} catch (\Exception $e) {
            // catch error if any
        }
    }
}