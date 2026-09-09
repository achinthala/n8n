<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\AsyncOrder;

use Dcw\OrderPendingReview\Model\QuoteFailedPaymentTracker;
use Magento\AsyncOrder\Api\AsyncPaymentInformationGuestPublisherInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Magento_AsyncOrder overrides webapi routes so POST guest payment-information hits this service, not
 * GuestPaymentInformationManagement. When async order is enabled and the payment method is not in the
 * synchronous list, placement runs inside {@see \Magento\AsyncOrder\Model\AsyncPaymentInformationGuestPublisher}
 * without delegating — our checkout plugins on GuestPaymentInformationManagementInterface never run.
 */
class AsyncPaymentInformationGuestPublisherPlugin
{
    public function __construct(
        private readonly QuoteFailedPaymentTracker $failedPaymentTracker
    ) {
    }

    /**
     * @param mixed $cartId
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        AsyncPaymentInformationGuestPublisherInterface $subject,
        \Closure $proceed,
        $cartId,
        string $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            return $proceed($cartId, $email, $paymentMethod, $billingAddress);
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
