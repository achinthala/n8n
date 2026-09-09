<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;

class AddPoPendingReviewStatus implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();

        $statusTable = $this->moduleDataSetup->getTable('sales_order_status');
        $stateTable = $this->moduleDataSetup->getTable('sales_order_status_state');

        $exists = $connection->fetchOne(
            $connection->select()->from($statusTable, ['status'])->where('status = ?', 'po_pending_review')
        );
        if (!$exists) {
            $connection->insert($statusTable, [
                'status' => 'po_pending_review',
                'label' => 'Pending Review',
            ]);
        }

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
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
