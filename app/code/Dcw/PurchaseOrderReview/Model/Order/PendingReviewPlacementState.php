<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Model\Order;

use Magento\Sales\Model\Order;

/**
 * Order state when applying po_pending_review after place order without overwriting gateway state (e.g. Auth.net).
 * Used by Dcw_PurchaseOrderReview and Dcw_OrderPendingReview.
 */
class PendingReviewPlacementState
{
    /**
     * Preserve processing when the payment method has already set it; use new for pending_payment (invoice path).
     */
    public static function resolveAfterPlace(string $orderState): string
    {
        if ($orderState === Order::STATE_PROCESSING) {
            return Order::STATE_PROCESSING;
        }

        if ($orderState === Order::STATE_PENDING_PAYMENT) {
            return Order::STATE_NEW;
        }

        if ($orderState === Order::STATE_NEW) {
            return Order::STATE_NEW;
        }

        return Order::STATE_NEW;
    }
}
