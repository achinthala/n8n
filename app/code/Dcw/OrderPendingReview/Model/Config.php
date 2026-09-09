<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model;

/**
 * Order status applied when hold reasons are present. Change in code if your project uses a different code.
 */
class Config
{
    public const PENDING_REVIEW_ORDER_STATUS = 'po_pending_review';
}
