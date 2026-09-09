<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Quote;

use Dcw\OrderPendingReview\Model\QuoteFailedPaymentTracker;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartManagementInterface;

/**
 * Guest REST can place orders via PUT /V1/guest-carts/:cartId/order (two-step checkout:
 * set-payment-information, then placeOrder) without calling savePaymentInformationAndPlaceOrder.
 * This plugin mirrors failed-attempt counting for that path. Masked cart id is passed through to
 * QuoteFailedPaymentTracker (same as GuestPaymentInformationManagementPlugin).
 */
class GuestCartManagementPlugin
{
    public function __construct(
        private readonly QuoteFailedPaymentTracker $failedPaymentTracker
    ) {
    }

    /**
     * @param mixed $cartId Masked guest cart id
     */
    public function aroundPlaceOrder(
        GuestCartManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        PaymentInterface $paymentMethod = null
    ) {
        try {
            return $proceed($cartId, $paymentMethod);
        } catch (\Throwable $e) {
            if ($this->failedPaymentTracker->isNonPaymentFailure($e)) {
                throw $e;
            }
            $context = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ];
            if ($paymentMethod) {
                $context['payment_method'] = $paymentMethod->getMethod();
            }
            $this->failedPaymentTracker->increment($cartId, $context);
            throw $e;
        }
    }
}
