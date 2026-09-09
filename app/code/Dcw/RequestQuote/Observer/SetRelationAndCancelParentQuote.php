<?php
/**
 * Observer to set relation and cancel parent quote when edited quote is submitted
 */

namespace Dcw\RequestQuote\Observer;

use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Amasty\RequestQuote\Api\QuoteServiceInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Observer for amasty_request_quote_submit_after event
 * Cancels the parent quote when a new edited quote is submitted
 */
class SetRelationAndCancelParentQuote implements ObserverInterface
{
    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteServiceInterface
     */
    private $quoteService;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param QuoteRepositoryInterface $quoteRepository
     * @param QuoteServiceInterface $quoteService
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        QuoteRepositoryInterface $quoteRepository,
        QuoteServiceInterface $quoteService,
        ResourceConnection $resourceConnection
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteService = $quoteService;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        // Try multiple ways to get the quote from the observer (matching RequestQuoteAfterObserver pattern)
        $quote = $observer->getEvent()->getQuote();
        if (!$quote) {
            $quote = $observer->getData('quote');
        }
        if (!$quote) {
            $quote = $observer->getEvent()->getData('quote');
        }

        if (!$quote || !$quote->getId()) {
            return;
        }

        // Check if this quote has a parent quote (was created from editing)
        $parentQuoteId = (int)$quote->getRelationParentId();
        
        // If relationParentId is not set on the quote object, try to reload from repository
        if (!$parentQuoteId && $quote->getId()) {
            try {
                $reloadedQuote = $this->quoteRepository->get($quote->getId());
                $parentQuoteId = (int)$reloadedQuote->getRelationParentId();
                
                if ($parentQuoteId) {
                    // Use the reloaded quote for the observer
                    $quote = $reloadedQuote;
                } else {
                    // Try direct database query as last resort
                    try {
                        $connection = $this->resourceConnection->getConnection();
                        // Use amasty_quote table (extension table for Amasty quotes)
                        $amastyQuoteTable = $connection->getTableName('amasty_quote');
                        $select = $connection->select()
                            ->from($amastyQuoteTable, ['relation_parent_id'])
                            ->where('quote_id = ?', $quote->getId());
                        $result = $connection->fetchOne($select);
                        if ($result) {
                            $parentQuoteId = (int)$result;
                        }
                    } catch (\Exception $e) {
                        // Silent fail - continue without canceling parent quote
                    }
                }
            } catch (\Exception $e) {
                // Silent fail - continue without canceling parent quote
            }
        }
        
        if ($parentQuoteId) {
            try {
                $parentQuote = $this->quoteRepository->get($parentQuoteId);
                
                // Set the child relation on parent quote (link back)
                $parentQuote->setRelationChildId((int)$quote->getEntityId());
                $this->quoteRepository->save($parentQuote);
                
                // Try to cancel the parent quote using the service method
                try {
                    $this->quoteService->cancelQuote((int)$parentQuote->getId());
                } catch (\Exception $e) {
                    // If cancelQuote fails, directly set status to CANCELED (4)
                    $parentQuote->setStatus(4); // CANCELED status id is 4
                    $this->quoteRepository->save($parentQuote);
                }
            } catch (\Exception $e) {
                // Silent fail - don't break the quote submission
            }
        }
    }
}

