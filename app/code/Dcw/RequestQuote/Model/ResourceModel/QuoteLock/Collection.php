<?php
/**
 * Quote Lock Collection
 */

namespace Dcw\RequestQuote\Model\ResourceModel\QuoteLock;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection
     */
    protected function _construct()
    {
        $this->_init(
            \Dcw\RequestQuote\Model\QuoteLock::class,
            \Dcw\RequestQuote\Model\ResourceModel\QuoteLock::class
        );
    }
}

