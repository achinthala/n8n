<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Checkout;

use Dcw\OrderPendingReview\Model\QuoteFailedPaymentTracker;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Restores failed-attempt counting for REST checkout (guest). sortOrder 100 runs inside ParadoxLabs TokenBase (10).
 */
class GuestPaymentInformationManagementPlugin
{
    public function __construct(
        private readonly QuoteFailedPaymentTracker $failedPaymentTracker
    ) {
    }

    /**
     * @param mixed $cartId
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
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
