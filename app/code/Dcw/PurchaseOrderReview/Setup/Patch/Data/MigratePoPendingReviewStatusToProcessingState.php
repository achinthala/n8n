<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Historical patch name retained for Magento patch registry compatibility.
 *
 * Earlier versions wrote {@see AddPoPendingReviewStatus} rows or processed migrations here.
 * Canonical fix: {@see EnsurePoPendingReviewUsesNewOrderState} (idempotent, runs on every deploy once).
 *
 * Intentionally empty — do not re-add side effects without a new patch class.
 */
class MigratePoPendingReviewStatusToProcessingState implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): void
    {
        // No-op — see EnsurePoPendingReviewUsesNewOrderState.
    }

    public static function getDependencies(): array
    {
        return [AddPoPendingReviewStatus::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
