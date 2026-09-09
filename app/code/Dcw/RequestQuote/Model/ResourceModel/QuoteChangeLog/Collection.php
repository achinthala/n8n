<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Model\ResourceModel\QuoteChangeLog;

use Dcw\RequestQuote\Model\QuoteChangeLog;
use Dcw\RequestQuote\Model\ResourceModel\QuoteChangeLog as QuoteChangeLogResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Quote Change Log Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(QuoteChangeLog::class, QuoteChangeLogResource::class);
    }

    /**
     * Filter by quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function addQuoteIdFilter(int $quoteId): self
    {
        return $this->addFieldToFilter('quote_id', $quoteId);
    }

    /**
     * Order by created at descending
     *
     * @return $this
     */
    public function orderByCreatedAtDesc(): self
    {
        return $this->setOrder('created_at', self::SORT_ORDER_DESC);
    }
}

