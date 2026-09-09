<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\SplitPayment;

use Dcw\OrderPendingReview\Model\QuoteFailedPaymentTracker;
use Dcw\SplitPayment\Controller\Index\PlaceOrder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\Json;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

/**
 * Split payment uses its own controller instead of PaymentInformationManagement; failed CVV/auth paths
 * return JSON without throwing, so the checkout payment plugins never run. Mirror that behavior here.
 */
class PlaceOrderPlugin
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly QuoteFailedPaymentTracker $failedPaymentTracker,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(PlaceOrder $subject, $result)
    {
        if (!$result instanceof Json) {
            return $result;
        }
        $payload = $this->decodeJsonResultPayload($result);
        if ($payload === null) {
            return $result;
        }
        $success = $payload['success'] ?? null;
        if ($success !== 0 && $success !== '0') {
            return $result;
        }
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $this->failedPaymentTracker->increment($quoteId, [
                'source' => 'split_payment',
            ]);
        } else {
            $this->logger->warning(QuoteFailedPaymentTracker::LOG_PREFIX . ' split_payment: success=0 but no quote id in session');
        }
        return $result;
    }

    /**
     * Json result stores the serialized body in a protected property; there is no public getter.
     *
     * @return array<string, mixed>|null
     */
    private function decodeJsonResultPayload(Json $result): ?array
    {
        try {
            $prop = new ReflectionProperty(Json::class, 'json');
            $prop->setAccessible(true);
            $json = $prop->getValue($result);
            if (!is_string($json) || $json === '') {
                return null;
            }
            $data = json_decode($json, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
