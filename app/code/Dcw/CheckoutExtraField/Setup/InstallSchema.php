<?php
namespace Dcw\CheckoutExtraField\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
{
$installer = $setup;
$installer->startSetup();

// Add phone_ext column to quote_address and sales_order_address tables
$installer->getConnection()->addColumn(
$installer->getTable('quote_address'),
'phone_ext',
[
'type' => Table::TYPE_TEXT,
'length' => 255,
'nullable' => true,
'comment' => 'Phone Extension'
]
);
$installer->getConnection()->addColumn(
$installer->getTable('sales_order_address'),
'phone_ext',
[
'type' => Table::TYPE_TEXT,
'length' => 255,
'nullable' => true,
'comment' => 'Phone Extension'
]
);

$installer->endSetup();
}
}