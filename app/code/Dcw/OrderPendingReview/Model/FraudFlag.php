<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model;

/**
 * Stored on quote and order as dcw_fraud_flag (smallint).
 */
final class FraudFlag
{
    public const NONE = 0;
    /** Base grand total ≥ $1K and billing/shipping state differ */
    public const ADDRESS_MISMATCH_1K = 1;
    /** Two or more failed place-order attempts on the quote */
    public const FAILED_PAYMENTS = 2;

    private const MIN_BASE_GRAND_TOTAL = 1000.0;
    private const MIN_FAILED_ATTEMPTS = 2;

    public static function minBaseGrandTotal(): float
    {
        return self::MIN_BASE_GRAND_TOTAL;
    }

    public static function minFailedAttempts(): int
    {
        return self::MIN_FAILED_ATTEMPTS;
    }
}
