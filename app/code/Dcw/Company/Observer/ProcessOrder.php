<?php

namespace Dcw\Company\Observer;

use Exception;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Dcw\Company\Helper\Data;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class ProcessOrder implements ObserverInterface
{
    protected $order;
    protected $logger;

    public function __construct(
        OrderInterface $order,
        Data $helperData,
        LoggerInterface $logger
    ) {
        $this->order = $order;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderId = $observer->getEvent()->getOrderIds();

        if ($orderId) {
            $lastOrder = $this->order->load($orderId);
            $itemCollection = $lastOrder->getItemsCollection();
            $customerId = $lastOrder->getCustomerId();

            $totalWeight = 0;

            foreach ($itemCollection as $key => $item) {

                if (!$item->getParentItemId()) {
                    $totalWeight+= $item->getRowWeight();
                }

                if ($item->getProduct()->getIncstoresFreeProduct() == 1) {
                    $tshirtStatus= $this->helperData->getTshirtAvailedValue();
                    $this->helperData->updateCustomerData($customerId, $tshirtStatus);
                }

                $item->setRowWeight($item->getWeight() * $item->getQtyOrdered());

                try {
                    $item->save(); //update order line item weight
                } catch (Exception $e) {
                    $this->logger->info('An error occurred: ' . $e->getMessage());
                }
            }

            if ($totalWeight == 0) {
                $totalWeight = $lastOrder->getRowWeight();
            }

            $lastOrder->setWeight($totalWeight); //update total order weight

            try {
                $lastOrder->save();
            } catch (Exception $e) {
                $this->logger->info('An error occurred: ' . $e->getMessage());
            }
        }
    }
}
