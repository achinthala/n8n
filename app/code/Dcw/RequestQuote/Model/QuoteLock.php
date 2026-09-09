<?php
/**
 * Quote Lock Model
 */

namespace Dcw\RequestQuote\Model;

use Magento\Framework\Model\AbstractModel;

class QuoteLock extends AbstractModel
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init(\Dcw\RequestQuote\Model\ResourceModel\QuoteLock::class);
    }
}

