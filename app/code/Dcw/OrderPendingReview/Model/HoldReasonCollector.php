<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model;

use Dcw\OrderPendingReview\Api\HoldReasonCollectorInterface;
use Dcw\OrderPendingReview\Model\Integration\OrderRestrictionPendingReviewMatcher;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Hold reasons come only from Order Restriction rules with apply_type = pending_review
 * (e.g. Fraud flag, Payment method — configure in Marketing → Order Restrictions).
 */
class HoldReasonCollector implements HoldReasonCollectorInterface
{
    public function __construct(
        private readonly OrderRestrictionPendingReviewMatcher $orderRestrictionPendingReviewMatcher
    ) {
    }

    /**
     * @inheritdoc
     * @return list<array<string, mixed>>
     */
    public function collect(OrderInterface $order, ?CartInterface $quote = null): array
    {
        $rulePayloads = $this->orderRestrictionPendingReviewMatcher->getMatchingRules($order);
        usort(
            $rulePayloads,
            static fn (array $a, array $b): int => ((int) ($a['restriction_id'] ?? 0)) <=> ((int) ($b['restriction_id'] ?? 0))
        );

        return $rulePayloads;
    }
}
