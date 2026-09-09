<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Observer;

use Dcw\PurchaseOrderReview\Model\PaymentMethod;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Enforces PO contact fields at order submit. {@see PaymentMethod::validate()} allows an empty set when
 * payment is first saved (e.g. Web API set-payment-information with method only).
 */
class ValidatePoGatewayContactBeforeQuoteSubmit implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $quote = $observer->getData('quote');
        if (!$quote instanceof CartInterface) {
            return;
        }
        $payment = $quote->getPayment();
        if ($payment->getMethod() !== PaymentMethod::METHOD_CODE) {
            return;
        }
        $method = $payment->getMethodInstance();
        if (!$method instanceof PaymentMethod) {
            return;
        }
        $method->setInfoInstance($payment);
        $method->validateForOrderSubmit();
    }
}
