<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Model;

use Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface;
use Dcw\RequestQuote\Api\QuoteChangeLogRepositoryInterface;
use Dcw\RequestQuote\Model\ResourceModel\QuoteChangeLog\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Quote Change Log Repository
 */
class QuoteChangeLogRepository implements QuoteChangeLogRepositoryInterface
{
    /**
     * @param QuoteChangeLogFactory $changeLogFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly QuoteChangeLogFactory $changeLogFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(QuoteChangeLogInterface $changeLog): QuoteChangeLogInterface
    {
        try {
            $changeLog->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save change log: %1', $e->getMessage()));
        }
        return $changeLog;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $logId): QuoteChangeLogInterface
    {
        $changeLog = $this->changeLogFactory->create();
        $changeLog->load($logId);
        
        if (!$changeLog->getId()) {
            throw new NoSuchEntityException(__('Change log with ID "%1" does not exist.', $logId));
        }
        
        return $changeLog;
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteId(int $quoteId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addQuoteIdFilter($quoteId);
        $collection->orderByCreatedAtDesc();
        
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function delete(QuoteChangeLogInterface $changeLog): bool
    {
        try {
            $changeLog->delete();
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete change log: %1', $e->getMessage()));
        }
        return true;
    }
}

