<?php
/**
 * Quote Lock Resource Model
 */

namespace Dcw\RequestQuote\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuoteLock extends AbstractDb
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('quote_lock', 'lock_id');
    }
}

