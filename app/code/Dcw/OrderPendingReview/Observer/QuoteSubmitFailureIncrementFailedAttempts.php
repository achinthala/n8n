<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Observer;

use Dcw\OrderPendingReview\Model\QuoteFailedPaymentTracker;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Increments dcw_failed_payment_attempts when quote→order submit fails (orderManagement::place throws).
 * Same moment as ParadoxLabs TokenBase CheckoutFailureRecordIncidentObserver (session tokenbase_failures).
 * Deduped with QuoteFailedPaymentTracker (registry) when the checkout around plugin also runs.
 * Split-payment declines before quoteManagement->submit() are handled by Plugin\SplitPayment\PlaceOrderPlugin.
 */
class QuoteSubmitFailureIncrementFailedAttempts implements ObserverInterface
{
    public function __construct(
        private readonly QuoteFailedPaymentTracker $failedPaymentTracker
    ) {
    }

    public function execute(Observer $observer): void
    {
        $exception = $observer->getData('exception');
        if ($exception instanceof \Throwable && $this->failedPaymentTracker->isNonPaymentFailure($exception)) {
            return;
        }
        $quote = $observer->getData('quote');
        if (!$quote instanceof CartInterface) {
            return;
        }
        $qid = (int) $quote->getId();
        if ($qid <= 0) {
            return;
        }
        $context = [];
        if ($exception instanceof \Throwable) {
            $context = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }
        $this->failedPaymentTracker->increment((string) $qid, $context);
    }
}
