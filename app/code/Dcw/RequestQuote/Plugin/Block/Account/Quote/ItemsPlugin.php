<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Plugin\Block\Account\Quote;

use Amasty\RequestQuote\Block\Account\Quote\Items;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;
use Amasty\RequestQuote\Model\QuoteRepository;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to ensure quote prices are recalculated before displaying items
 */
class ItemsPlugin
{
    public function __construct(
        private readonly RequestQuoteViewModel $requestQuoteViewModel,
        private readonly QuoteRepository $quoteRepository,
        private readonly MessageManagerInterface $messageManager,
        private readonly SessionManagerInterface $sessionManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Recalculate quote prices before getting quote
     * This ensures prices are updated on first page load
     *
     * @param Items    $subject
     * @param \Closure $proceed
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    public function aroundGetQuote(Items $subject, \Closure $proceed)
    {
        $quote = $proceed();

        if ($quote && $quote->getId()) {
            try {
                $hasPriceChanges = $this->requestQuoteViewModel->hasQuotePriceChanges($quote);
                $priceChangeValue = $hasPriceChanges ? 1 : 0;
                
                $this->setPriceChangeStatus($subject, $quote, $priceChangeValue);
                $this->requestQuoteViewModel->recalculateQuotePrices($quote, true);

                $quoteId = (int)$quote->getId();
                $reloadedQuote = $this->requestQuoteViewModel->reloadQuote($quoteId);

                if ($reloadedQuote) {
                    $this->quoteRepository->save($reloadedQuote);
                    $reloadedQuote = $this->requestQuoteViewModel->reloadQuote($quoteId);
                    $reloadedQuote->setTotalsCollectedFlag(false);
                    $reloadedQuote->collectTotals();
                    $this->quoteRepository->save($reloadedQuote);
                    $reloadedQuote = $this->requestQuoteViewModel->reloadQuote($quoteId);
                    $this->setPriceChangeStatus($subject, $reloadedQuote, $priceChangeValue);
                    $this->updateBlockQuote($subject, $reloadedQuote);
                    
                    if ($hasPriceChanges) {
                        $sessionKey = 'price_change_notice_shown_view_' . $quoteId;
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
            } catch (\Exception $e) {
                $this->logger->error(
                    'ItemsPlugin: Error recalculating quote prices',
                    ['quote_id' => $quote->getId(), 'error' => $e->getMessage()]
                );
            }
        }

        return $quote;
    }

    /**
     * Set price change status in block and quote data
     *
     * @param Items $block
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $quote
     * @param int $value
     * @return void
     */
    private function setPriceChangeStatus(Items $block, $quote, int $value): void
    {
        $block->setData('has_price_changes', $value);
        $quote->setData('has_price_changes', $value);
    }

    /**
     * Update block's internal quote reference using reflection
     *
     * @param Items $block
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $quote
     * @return void
     */
    private function updateBlockQuote(Items $block, $quote): void
    {
        try {
            $reflection = new \ReflectionClass($block);
            $propertyNames = ['_quote', 'quote'];
            
            foreach ($propertyNames as $propertyName) {
                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    $property->setValue($block, $quote);
                    break; // Only need to update one
                }
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }
    }

    /**
     * Clear block's cached items using reflection
     *
     * @param Items $block
     * @return void
     */
    private function clearBlockItemsCache(Items $block): void
    {
        try {
            $reflection = new \ReflectionClass($block);
            $propertyNames = ['_items', 'items'];
            
            foreach ($propertyNames as $propertyName) {
                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    $property->setValue($block, null);
                    break; // Only need to clear one
                }
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }
    }
}

