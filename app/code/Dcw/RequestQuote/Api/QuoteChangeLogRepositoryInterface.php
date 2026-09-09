<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Api;

use Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface;

/**
 * Quote Change Log Repository Interface
 */
interface QuoteChangeLogRepositoryInterface
{
    /**
     * Save change log
     *
     * @param QuoteChangeLogInterface $changeLog
     * @return QuoteChangeLogInterface
     */
    public function save(QuoteChangeLogInterface $changeLog): QuoteChangeLogInterface;

    /**
     * Get change log by ID
     *
     * @param int $logId
     * @return QuoteChangeLogInterface
     */
    public function getById(int $logId): QuoteChangeLogInterface;

    /**
     * Get change logs by quote ID
     *
     * @param int $quoteId
     * @return QuoteChangeLogInterface[]
     */
    public function getByQuoteId(int $quoteId): array;

    /**
     * Delete change log
     *
     * @param QuoteChangeLogInterface $changeLog
     * @return bool
     */
    public function delete(QuoteChangeLogInterface $changeLog): bool;
}

