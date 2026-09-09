<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Api;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

interface HoldReasonCollectorInterface
{
    /**
     * Return pending-review reasons from Order Restriction rules (apply_type = pending_review):
     * structured payloads (type order_restriction, name, title, restriction_id, etc.).
     *
     * @return list<array<string, mixed>>
     */
    public function collect(OrderInterface $order, ?CartInterface $quote = null): array;
}
