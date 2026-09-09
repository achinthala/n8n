<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Sales;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepositoryPlugin
{
    public function __construct(
        private readonly OrderExtensionFactory $orderExtensionFactory
    ) {
    }

    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        $this->hydrate($order);
        return $order;
    }

    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResult
    ): OrderSearchResultInterface {
        foreach ($searchResult->getItems() as $order) {
            $this->hydrate($order);
        }
        return $searchResult;
    }

    private function hydrate(OrderInterface $order): void
    {
        $ext = $order->getExtensionAttributes();
        if ($ext === null) {
            $ext = $this->orderExtensionFactory->create();
        }
        $raw = $order->getData('dcw_pending_review_reasons');
        if ($raw !== null && $raw !== '') {
            $ext->setDcwPendingReviewReasons((string) $raw);
        }
        $ext->setDcwFraudFlag((int) $order->getData('dcw_fraud_flag'));
        $order->setExtensionAttributes($ext);
    }
}
