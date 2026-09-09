<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Observer;

use Dcw\OrderPendingReview\Model\Rule\Condition\FraudFlagEquals;
use Dcw\OrderPendingReview\Model\Rule\Condition\PaymentMethodEquals;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Adds "Fraud flag" condition to SalesRule combine (Order Restrictions Conditions tab).
 */
class AddSalesRuleCombineConditions implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $additional = $observer->getEvent()->getData('additional');
        if ($additional === null) {
            return;
        }
        $existing = $additional->getConditions();
        $group = [
            'label' => __('Order Pending Review'),
            'value' => [
                [
                    'value' => FraudFlagEquals::class,
                    'label' => __('Fraud flag (1 or 2)'),
                ],
                [
                    'value' => PaymentMethodEquals::class,
                    'label' => __('Payment method'),
                ],
            ],
        ];
        $additional->setConditions(array_merge($existing ?: [], [$group]));
    }
}
