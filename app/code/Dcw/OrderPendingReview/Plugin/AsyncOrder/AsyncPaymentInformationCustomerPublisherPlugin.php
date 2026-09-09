<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\AsyncOrder;

use Dcw\OrderPendingReview\Model\QuoteFailedPaymentTracker;
use Magento\AsyncOrder\Api\AsyncPaymentInformationCustomerPublisherInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Same as {@see AsyncPaymentInformationGuestPublisherPlugin} for logged-in customers when Async Order
 * routes POST carts/mine/payment-information to the async publisher.
 */
class AsyncPaymentInformationCustomerPublisherPlugin
{
    public function __construct(
        private readonly QuoteFailedPaymentTracker $failedPaymentTracker
    ) {
    }

    public function aroundSavePaymentInformationAndPlaceOrder(
        AsyncPaymentInformationCustomerPublisherInterface $subject,
        \Closure $proceed,
        string $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            return $proceed($cartId, $paymentMethod, $billingAddress);
        } catch (\Throwable $e) {
            if ($this->failedPaymentTracker->isNonPaymentFailure($e)) {
                throw $e;
            }
            $this->failedPaymentTracker->increment($cartId, [
                'payment_method' => $paymentMethod->getMethod(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
