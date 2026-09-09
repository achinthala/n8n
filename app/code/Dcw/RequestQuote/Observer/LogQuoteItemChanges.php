<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Observer;

use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Dcw\RequestQuote\Service\QuoteChangeLogger;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Observer to log quote item changes (Amasty quotes only)
 */
class LogQuoteItemChanges implements ObserverInterface
{
    /**
     * @param QuoteChangeLogger $changeLogger
     * @param QuoteRepositoryInterface $amastyQuoteRepository
     * @param State $appState
     */
    public function __construct(
        private readonly QuoteChangeLogger $changeLogger,
        private readonly QuoteRepositoryInterface $amastyQuoteRepository,
        private readonly State $appState
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        // Skip logging in admin area - let SavePlugin handle admin changes
        try {
            if ($this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML) {
                return;
            }
        } catch (\Exception $e) {
            // If area code is not set, continue (might be CLI or API)
        }
        
        /** @var Item|null $item */
        $item = $observer->getEvent()->getData('quote_item');
        
        if (!$item) {
            return;
        }
        
        $quote = $item->getQuote();
        
        if (!$quote || !$quote->getId()) {
            return;
        }

        $quoteId = (int)$quote->getId();
        
        // Only log changes for Amasty quotes, not regular Magento quotes
        try {
            if (!$this->amastyQuoteRepository->isAmastyQuote($quoteId)) {
                return;
            }
        } catch (\Exception $e) {
            // If we can't determine, skip logging to be safe
            return;
        }

        // Skip if item doesn't have an ID yet (new items being added)
        if (!$item->getId()) {
            return;
        }

        $itemId = (int)$item->getId();
        
        // Get item data
        $itemData = [
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'qty' => $item->getQty(),
            'price' => $item->getPrice(),
            'row_total' => $item->getRowTotal(),
        ];

        $eventName = $observer->getEvent()->getName();
        
        switch ($eventName) {
            case 'sales_quote_item_save_after':
                // Check if this is a new item or update
                if ($item->isObjectNew()) {
                    $this->changeLogger->logItemAdded($quoteId, $itemId, $itemData);
                } else {
                    // For updates, we'd need to compare old vs new values
                    // This would require storing old values before save
                    // For now, we'll log as updated with current data
                    $this->changeLogger->logItemUpdated($quoteId, $itemId, [], $itemData);
                }
                break;
                
            case 'sales_quote_remove_item':
                $this->changeLogger->logItemRemoved($quoteId, $itemId, $itemData);
                break;
        }
    }
}

