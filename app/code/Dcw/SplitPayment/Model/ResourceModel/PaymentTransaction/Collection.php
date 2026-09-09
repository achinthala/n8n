<?php
namespace Dcw\SplitPayment\Model\ResourceModel\PaymentTransaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    // Specify the field name for the primary key in the database table
    protected $_idFieldName = 'split_payment_transaction_id';
    
    /**
     * Constructor method for the Collection class
     * Define model & resource model
     */
    protected function _construct()
    {
        // Initialize the collection with the model and resource model classes
        $this->_init(
            'Dcw\SplitPayment\Model\PaymentTransaction',
            'Dcw\SplitPayment\Model\ResourceModel\PaymentTransaction'
        );
    }
}
