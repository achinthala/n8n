<?php
/**
 * Processor to merge target quote into current quote for editing
 */

namespace Dcw\RequestQuote\Model;

use Amasty\RequestQuote\Api\Data\QuoteInterface;
use Amasty\RequestQuote\Model\Quote\Session;
use Amasty\RequestQuote\Model\QuoteRepository;
use Magento\Framework\Exception\LocalizedException;

class EditQuoteProcessor
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Session
     */
    private $amastyQuoteSession;

    /**
     * @param QuoteRepository $quoteRepository
     * @param Session $amastyQuoteSession
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        Session $amastyQuoteSession
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->amastyQuoteSession = $amastyQuoteSession;
    }

    /**
     * Execute quote edit processing
     * Loads the existing quote into the cart session for editing
     * Does not change the quote status - only updates when form is submitted
     *
     * @param QuoteInterface $targetQuote
     * @param QuoteInterface $currentQuote
     * @return array Returns information about the operation ['hadPreviousItems' => bool, 'previousQuoteId' => int|null]
     * @throws LocalizedException
     */
    public function execute(QuoteInterface $targetQuote, QuoteInterface $currentQuote): array
    {
        try {
            // Check if there's already a quote being edited
            $hadPreviousItems = false;
            $previousQuoteId = null;
            
            // Check if current quote has items or has a relationParentId
            $currentItems = $currentQuote->getAllItems();
            $currentRelationParentId = $currentQuote->getRelationParentId();
            $currentQuoteId = $currentQuote->getId();
            
            // If there are items or we're editing a different quote, we need to clear them
            if (count($currentItems) > 0 || ($currentRelationParentId && $currentRelationParentId != $targetQuote->getEntityId())) {
                $hadPreviousItems = true;
                $previousQuoteId = $currentRelationParentId ?: $currentQuoteId;
            }
            
            // Load the new quote into session
            $this->loadQuoteIntoSession($targetQuote, $currentQuote);
            
            return [
                'hadPreviousItems' => $hadPreviousItems,
                'previousQuoteId' => $previousQuoteId,
                'newQuoteId' => $targetQuote->getEntityId()
            ];
        } catch (\Throwable $e) {
            throw new LocalizedException(
                __('The quote couldn\'t be edited. Error: %1', $e->getMessage())
            );
        }
    }

    /**
     * Load existing quote into session for editing
     * Use the target quote directly - no need to clone items to a new quote
     * Set relation_parent_id on target quote to itself so it updates when saving
     * Temporarily set is_active = 1 in memory only (not saved) so getActive() works
     *
     * @param QuoteInterface $targetQuote The quote to edit
     * @param QuoteInterface $currentQuote The current cart quote (not used, we use target quote directly)
     * @return void
     */
    private function loadQuoteIntoSession(QuoteInterface $targetQuote, QuoteInterface $currentQuote): void
    {
        $targetQuoteId = (int)$targetQuote->getEntityId();
        
        // Reload target quote from repository to ensure fresh data with items
        $targetQuote = $this->quoteRepository->get($targetQuoteId);
        $targetQuote->getItemsCollection()->load(); // Ensure items are loaded
        
        // Set relation_parent_id on target quote to itself
        // This ensures when saving, it updates the existing quote instead of creating new
        $targetQuote->setRelationParentId($targetQuoteId);
        
        // Set store and collect totals
        $targetQuote->setTotalsCollectedFlag(false);
        $targetQuote->collectTotals();
        
        // Save the target quote with relation_parent_id set
        // Do NOT change is_active in database - preserve original status
        $this->quoteRepository->save($targetQuote);
        
        // CRITICAL: Temporarily set is_active = 1 in memory only (NOT saved to database)
        // This allows getActive() to work when getQuote() is called
        // We set it AFTER save so it's only in memory, not persisted
        $targetQuote->setIsActive(true);
        
        // CRITICAL: Use replaceQuote() to set the target quote directly in session
        // This uses the original quote (2279290) instead of creating a new one
        // The quote object has is_active = 1 in memory, so getActive() will work
        $this->amastyQuoteSession->replaceQuote($targetQuote);
        
        // CRITICAL: Explicitly set quote ID in session so getQuoteId() returns the correct ID
        // This ensures the quotecart section data provider can find the quote
        $this->amastyQuoteSession->setQuoteId($targetQuoteId);
        
        // CRITICAL: Call getQuote() after replaceQuote() to trigger the plugin
        // The plugin will reload from repository with items loaded (bypassing getActive() requirement)
        $this->amastyQuoteSession->getQuote();
    }
}

