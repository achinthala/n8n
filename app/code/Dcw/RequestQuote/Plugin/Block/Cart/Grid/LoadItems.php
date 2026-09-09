<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Plugin\Block\Cart\Grid;

use Amasty\RequestQuote\Block\Cart\Grid;
use Amasty\RequestQuote\Model\Quote\Session as AmastyQuoteSession;
use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Amasty\RequestQuote\Model\QuoteRepository;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to ensure quote items are loaded when Cart Grid block gets items
 */
class LoadItems
{
    /**
     * @param AmastyQuoteSession $amastyQuoteSession
     * @param QuoteRepositoryInterface $quoteRepository
     * @param QuoteRepository $amastyQuoteRepository
     * @param RequestQuoteViewModel $requestQuoteViewModel
     * @param MessageManagerInterface $messageManager
     * @param SessionManagerInterface $sessionManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly AmastyQuoteSession $amastyQuoteSession,
        private readonly QuoteRepositoryInterface $quoteRepository,
        private readonly QuoteRepository $amastyQuoteRepository,
        private readonly RequestQuoteViewModel $requestQuoteViewModel,
        private readonly MessageManagerInterface $messageManager,
        private readonly SessionManagerInterface $sessionManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Ensure Grid block gets quote from Amasty quote session (not checkout session)
     * Also recalculates prices and checks for price changes
     *
     * @param Grid $subject
     * @param \Closure $proceed
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface
     */
    public function aroundGetQuote(Grid $subject, \Closure $proceed)
    {
        $quoteId = $this->amastyQuoteSession->getQuoteId();
        
        if ($quoteId) {
            try {
                $quote = $this->quoteRepository->get((int)$quoteId);
                $itemsCollection = $quote->getItemsCollection();
                if (!$itemsCollection->isLoaded()) {
                    $itemsCollection->load();
                }
                
                if ($quote && $quote->getId()) {
                    $hasPriceChanges = $this->requestQuoteViewModel->hasQuotePriceChanges($quote);
                    $priceChangeValue = $hasPriceChanges ? 1 : 0;
                    
                    $subject->setData('has_price_changes', $priceChangeValue);
                    $quote->setData('has_price_changes', $priceChangeValue);
                    $this->requestQuoteViewModel->recalculateQuotePrices($quote, true);
                    $reloadedQuote = $this->requestQuoteViewModel->reloadQuote((int)$quoteId);
                    
                    if ($reloadedQuote) {
                        $this->amastyQuoteRepository->save($reloadedQuote);
                        $reloadedQuote = $this->requestQuoteViewModel->reloadQuote((int)$quoteId);
                        $reloadedQuote->setData('has_price_changes', $priceChangeValue);
                        $subject->setData('has_price_changes', $priceChangeValue);
                        
                        if ($hasPriceChanges) {
                            $sessionKey = 'price_change_notice_shown_cart_' . $quoteId;
                            $quoteUpdatedAt = $reloadedQuote->getUpdatedAt() ? strtotime($reloadedQuote->getUpdatedAt()) : 0;
                            $lastShownTimestamp = $this->sessionManager->getData($sessionKey);
                            
                            if (!$lastShownTimestamp || $quoteUpdatedAt > $lastShownTimestamp) {
                                $this->messageManager->addWarningMessage(
                                    __('Some of the quote prices are changed from the original quote. The current price reflects the latest product pricing.')
                                );
                                $this->sessionManager->setData($sessionKey, $quoteUpdatedAt);
                            }
                        }
                        
                        $this->clearBlockItemsCache($subject);
                        return $reloadedQuote;
                    }
                    
                    // If reload failed, still return quote with price change status set
                    $quote->setData('has_price_changes', $priceChangeValue);
                    $subject->setData('has_price_changes', $priceChangeValue);
                    return $quote;
                }
                
                // Fallback: return quote with items loaded
                return $quote;
            } catch (\Exception $e) {
                $this->logger->error('LoadItems Plugin: Failed to load quote', [
                    'quote_id' => $quoteId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $proceed();
    }

    /**
     * Ensure items are loaded from quote when Grid block gets items
     * If result is empty but quote has items, return quote items
     *
     * @param Grid $subject
     * @param array $result
     * @return array
     */
    public function afterGetItems(Grid $subject, array $result): array
    {
        if (empty($result)) {
            try {
                $quote = $subject->getQuote();
                if ($quote && $quote->getId()) {
                    $itemsCollection = $quote->getItemsCollection();
                    if (!$itemsCollection->isLoaded()) {
                        $itemsCollection->load();
                    }
                    
                    $visibleItems = $quote->getAllVisibleItems();
                    
                    if (!empty($visibleItems)) {
                        return array_values($visibleItems);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('LoadItems Plugin: afterGetItems - Error loading items', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $result;
    }

    /**
     * Clear block's cached items using reflection
     *
     * @param Grid $block
     * @return void
     */
    private function clearBlockItemsCache(Grid $block): void
    {
        try {
            $reflection = new \ReflectionClass($block);
            $propertyNames = ['_items', 'items'];
            
            foreach ($propertyNames as $propertyName) {
                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    $property->setValue($block, null);
                    break;
                }
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }
    }
}

