<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;

/**
 * Ensures status po_pending_review is valid for order states new and processing.
 * (Gateways often set processing before our observers run.)
 */
class EnsurePoPendingReviewUsesNewOrderState implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $stateTable = $this->moduleDataSetup->getTable('sales_order_status_state');
        $orderTable = $this->moduleDataSetup->getTable('sales_order');

        $connection->delete(
            $stateTable,
            [
                'status = ?' => 'po_pending_review',
                'state = ?' => Order::STATE_PENDING_PAYMENT,
            ]
        );

        foreach ([Order::STATE_NEW, Order::STATE_PROCESSING] as $state) {
            $exists = $connection->fetchOne(
                $connection->select()
                    ->from($stateTable, ['status'])
                    ->where('status = ?', 'po_pending_review')
                    ->where('state = ?', $state)
            );
            if (!$exists) {
                $connection->insert($stateTable, [
                    'status' => 'po_pending_review',
                    'state' => $state,
                    'is_default' => 0,
                    'visible_on_front' => 1,
                ]);
            }
        }

        $where = sprintf(
            'status = %s AND state = %s',
            $connection->quote('po_pending_review'),
            $connection->quote(Order::STATE_PENDING_PAYMENT)
        );
        $connection->update($orderTable, ['state' => Order::STATE_NEW], $where);
    }

    public static function getDependencies(): array
    {
        return [
            AddPoPendingReviewStatus::class,
            MigratePoPendingReviewStatusToProcessingState::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
