<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Quote Change Log Factory
 */
class QuoteChangeLogFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Create change log instance
     *
     * @param array $data
     * @return QuoteChangeLog
     */
    public function create(array $data = []): QuoteChangeLog
    {
        return $this->objectManager->create(QuoteChangeLog::class, $data);
    }
}

