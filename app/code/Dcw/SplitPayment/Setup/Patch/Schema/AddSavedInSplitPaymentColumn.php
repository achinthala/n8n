<?php
namespace Dcw\SplitPayment\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddSavedInSplitPaymentColumn implements SchemaPatchInterface
{
   private $moduleDataSetup;

   public function __construct(
       ModuleDataSetupInterface $moduleDataSetup
   ) {
       $this->moduleDataSetup = $moduleDataSetup;
   }

   public static function getDependencies()
   {
       return [];
   }

   public function getAliases()
   {
       return [];
   }

   public function apply()
   {
       $this->moduleDataSetup->startSetup();

       $this->moduleDataSetup->getConnection()->addColumn(
           $this->moduleDataSetup->getTable('paradoxlabs_stored_card'),
           'saved_in_splitpayment',
           [
               'type' => Table::TYPE_SMALLINT,
               'length' => 6,
               'nullable' => true,
               'default' => 0,
               'comment'  => 'Saved In SplitPayment',
           ]
       );

       $this->moduleDataSetup->endSetup();
   }
}