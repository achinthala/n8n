<?php

declare(strict_types=1);

namespace Dcw\DesignerTool\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Serialize\SerializerInterface;

class RestoreAdditionalOptionsFromOrder implements ObserverInterface
{
    protected $orderFactory;
    protected $request;
    protected $serializer;

    public function __construct(
        OrderFactory $orderFactory,
        RequestInterface $request,
        SerializerInterface $serializer
    ) {
        $this->orderFactory = $orderFactory;
        $this->request = $request;
        $this->serializer = $serializer;
    }

    public function execute(Observer $observer)
    {
        $items = [];

        // If single product
        if ($observer->getQuoteItem()) {
            $items[] = $observer->getQuoteItem();
        }

        // If multiple items
        if ($observer->getItems()) {
            $items = array_merge($items, $observer->getItems());
        }

        // Get order_id from reorder URL
        $orderId = (int) $this->request->getParam('order_id');
        if (!$orderId) {
            return;
        }

        // Load the order
        $order = $this->orderFactory->create()->load($orderId);
        if (!$order->getId()) {
            return;
        }

        // Match by SKU
        foreach ($items as $quoteItem) {
            if (!$quoteItem) {
                continue;
            }
    
            $currentSku = $quoteItem->getSku();
    
            foreach ($order->getAllItems() as $orderItem) {
                if ($orderItem->getSku() === $currentSku) {
                    $productOptions = $orderItem->getProductOptions();
                    if (isset($productOptions['additional_options'])) {
                        $quoteItem->addOption([
                            'code'       => 'additional_options',
                            'value'      => $this->serializer->serialize($productOptions['additional_options']),
                            'product_id' => $quoteItem->getProduct()->getId(),
                        ]);
                    }
                    break;
                }
            }
        }
    }
}
