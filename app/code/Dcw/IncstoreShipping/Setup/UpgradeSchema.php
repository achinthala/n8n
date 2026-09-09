<?php
namespace Dcw\IncstoreShipping\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
       

        $setup->startSetup();

        //if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addShippingMetadataColumn($setup);
            
            
        //}

        $setup->endSetup();
    }

    protected function addShippingMetadataColumn(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('sales_order_item');
        $columnName = 'shipping_metadata';

       

        $setup->getConnection()->addColumn(
            $tableName,
            $columnName,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Shipping Metadata',
            ]
        );
    }
}
