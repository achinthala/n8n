<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Observer;

use Dcw\OrderPendingReview\Api\HoldReasonCollectorInterface;
use Dcw\OrderPendingReview\Model\Config;
use Dcw\PurchaseOrderReview\Model\Order\PendingReviewPlacementState;
use Dcw\OrderPendingReview\Model\Quote\QuoteFraudFlagCalculator;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class ApplyPendingReviewAfterPlaceOrder implements ObserverInterface
{
    public function __construct(
        private readonly HoldReasonCollectorInterface $holdReasonCollector,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly QuoteFraudFlagCalculator $quoteFraudFlagCalculator,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof Order) {
            return;
        }

        $quote = null;
        if ($order->getQuoteId()) {
            try {
                $quote = $this->cartRepository->get((int) $order->getQuoteId());
            } catch (\Throwable) {
                $quote = null;
            }
        }

        if ($quote !== null) {
            // Align with place order / CheckRestriction: totals + fraud flag before rules read quote.dcw_fraud_flag
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $this->quoteFraudFlagCalculator->apply($quote);
            try {
                $this->cartRepository->save($quote);
            } catch (\Throwable) {
                // Non-fatal: matcher still loads quote; order flag may be stale if save fails
            }
            $order->setData('dcw_fraud_flag', (int) $quote->getData('dcw_fraud_flag'));
        } else {
            $order->setData('dcw_fraud_flag', 0);
        }

        $priorState = (string) $order->getState();

        $reasons = $this->holdReasonCollector->collect($order, $quote);
        if ($reasons === []) {
            $this->resetFailedAttemptsOnQuote($quote);
            $this->orderRepository->save($order);

            return;
        }

        $order->setData('dcw_pending_review_reasons', (string) \json_encode($reasons, JSON_UNESCAPED_UNICODE));

        $order->setState(PendingReviewPlacementState::resolveAfterPlace($priorState))
            ->setStatus(Config::PENDING_REVIEW_ORDER_STATUS);

        $this->orderRepository->save($order);

        $this->resetFailedAttemptsOnQuote($quote);
    }

    private function resetFailedAttemptsOnQuote(?CartInterface $quote): void
    {
        if ($quote === null || (int) $quote->getData('dcw_failed_payment_attempts') === 0) {
            return;
        }
        $quote->setData('dcw_failed_payment_attempts', 0);
        $qid = (int) $quote->getId();
        if ($qid > 0) {
            $connection = $this->resourceConnection->getConnection();
            $connection->update(
                $this->resourceConnection->getTableName('quote'),
                ['dcw_failed_payment_attempts' => 0],
                ['entity_id = ?' => $qid]
            );
        }
        try {
            $this->cartRepository->save($quote);
        } catch (\Throwable) {
        }
    }
}
