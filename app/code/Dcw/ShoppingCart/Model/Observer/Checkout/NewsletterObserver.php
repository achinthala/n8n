<?php

namespace Dcw\ShoppingCart\Model\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\SubscriberFactory;

class NewsletterObserver implements ObserverInterface
{
    private $subscriberFactory;

    public function __construct(
        SubscriberFactory $subscriberFactory
    ) {
        $this->subscriberFactory = $subscriberFactory;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        $billingAddress = $order->getBillingAddress();
        $isSubscribed = $observer->getRequest()->getParam('is_subscribed');

        if ($isSubscribed && $billingAddress->getEmail()) {
            $subscriber = $this->subscriberFactory->create();
            $subscriber->subscribe($billingAddress->getEmail());
        }
    }
}
