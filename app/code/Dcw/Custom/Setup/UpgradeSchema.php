<?php

namespace Dcw\Custom\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * remove the cart_label from the amasty_quote table
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('amasty_quote');

            if ($connection->tableColumnExists($tableName, 'cart_label')) {
                $connection->dropColumn($tableName, 'cart_label');
            }
        }

        $setup->endSetup();
    }
}
