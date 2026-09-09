<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class MigrateShareCartContactFields implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $table = $this->moduleDataSetup->getTable('dcw_share_cart');
        $connection = $this->moduleDataSetup->getConnection();

        if (!$connection->isTableExists($table)) {
            $this->logger->info('ShareCart migration skipped: dcw_share_cart table does not exist.');
            $this->moduleDataSetup->getConnection()->endSetup();

            return $this;
        }

        $quotedTable = $connection->quoteIdentifier($table);

        $yourNameUpdated = $connection->query(
            "UPDATE {$quotedTable}
             SET your_name = shared_by_name
             WHERE (your_name IS NULL OR your_name = '')
               AND shared_by_name IS NOT NULL
               AND shared_by_name != ''"
        )->rowCount();

        $recipientNameUpdated = $connection->query(
            "UPDATE {$quotedTable}
             SET recipient_name = TRIM(CONCAT(IFNULL(recipient_first_name, ''), ' ', IFNULL(recipient_last_name, '')))
             WHERE (recipient_name IS NULL OR recipient_name = '')
               AND (
                   (recipient_first_name IS NOT NULL AND recipient_first_name != '')
                   OR (recipient_last_name IS NOT NULL AND recipient_last_name != '')
               )"
        )->rowCount();

        $recipientPhoneUpdated = $connection->query(
            "UPDATE {$quotedTable}
             SET recipient_phone = sender_phone
             WHERE (recipient_phone IS NULL OR recipient_phone = '')
               AND sender_phone IS NOT NULL
               AND sender_phone != ''"
        )->rowCount();

        $this->logger->info(sprintf(
            'ShareCart contact field migration completed | your_name rows: %d | recipient_name rows: %d | recipient_phone rows: %d',
            $yourNameUpdated,
            $recipientNameUpdated,
            $recipientPhoneUpdated
        ));

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
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
