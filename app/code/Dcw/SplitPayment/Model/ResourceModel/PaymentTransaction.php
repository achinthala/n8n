<?php
namespace Dcw\SplitPayment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PaymentTransaction extends AbstractDb
{
    /**
     * Constructor method for the PaymentTransaction model
     * Define main table
     */
    protected function _construct()
    {
        // Initialize the model, specifying the main table and primary key
        $this->_init(
            'split_payment_transaction', 
            'split_payment_transaction_id'
        ); 
    }
}
