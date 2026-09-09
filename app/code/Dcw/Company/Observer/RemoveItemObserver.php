<?php

namespace Dcw\Company\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Dcw\Company\Helper\Data;

class RemoveItemObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @Data Data
     */
    protected $helperData;

    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     * @param Data $helperData
     * @return $this
     */
    public function __construct(
        RequestInterface $request,
        Data $helperData,
        \Magento\Catalog\Model\ProductFactory $_productloader
    ) {
        $this->_request = $request;
        $this->helperData = $helperData;
        $this->_productloader = $_productloader;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_request->getFullActionName() == 'checkout_cart_delete') {
            $quoteItem = $observer->getQuoteItem();
            $quote = $quoteItem->getQuote();
            $product = $quoteItem->getProduct();
            $sku=$product->getSku();
            if ($product->getIncstoresFreeProduct() == 1) {
                $customerId = $quote->getCustomer()->getId();
                $tshirtStatus= $this->helperData->getTshirtDeclinedValue();
                $this->helperData->updateCustomerData($customerId, $tshirtStatus);
            }
        }
    }
}
