<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model;

/**
 * Machine-readable codes stored in sales_order.dcw_pending_review_reasons (JSON array).
 * Order Restriction matches use structured payloads ({@see self::TYPE_ORDER_RESTRICTION}) with name/title.
 */
class PendingReviewReason
{
    public const SPLIT_PAYMENT = 'split_payment';
    public const PURCHASE_ORDER = 'purchase_order';

    /** @deprecated Legacy single code; new orders use structured {@see self::TYPE_ORDER_RESTRICTION} payloads */
    public const ORDER_RESTRICTION_RULE = 'order_restriction_rule';

    /** JSON object key for structured reasons */
    public const FIELD_TYPE = 'type';
    /** Value for {@see self::FIELD_TYPE}: order restriction rule match with name/title fields */
    public const TYPE_ORDER_RESTRICTION = 'order_restriction';

    /**
     * Labels for legacy string codes still present on older orders. Active PO/split codes fall back to raw code in UI.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::ORDER_RESTRICTION_RULE => 'Order restriction rule (pending review)',
            self::PURCHASE_ORDER => 'Purchase order',
            self::SPLIT_PAYMENT => 'Split payment',
        ];
    }
}
