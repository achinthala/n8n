<?php
namespace Dcw\SplitPayment\Model;

use Magento\Framework\Model\AbstractModel;

class PaymentTransaction extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
         // Initialize the resource model for the SplitPayment entity
        $this->_init('Dcw\SplitPayment\Model\ResourceModel\PaymentTransaction');
    }
}