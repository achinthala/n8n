<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Plugin\Quote\Session;

use Amasty\RequestQuote\Model\Quote\Session;
use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Plugin to ensure quote has items loaded when retrieved from session
 */
class LoadQuoteItems
{
    /**
     * @param QuoteRepositoryInterface $quoteRepository
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly QuoteRepositoryInterface $quoteRepository,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Intercept getQuote() BEFORE Amasty's logic runs
     * This allows us to bypass getActive() requirement and load inactive quotes
     *
     * @param Session $subject
     * @param \Closure $proceed
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface
     */
    public function aroundGetQuote(Session $subject, \Closure $proceed)
    {
        $sessionQuoteId = $subject->getQuoteId();
        
        // If session has quote ID, load from repository using get() (not getActive())
        // This bypasses the is_active requirement and ensures items are loaded
        if ($sessionQuoteId) {
            try {
                // Use get() instead of getActive() - this works for inactive quotes
                $sessionQuote = $this->quoteRepository->get((int)$sessionQuoteId);
                $sessionQuote->getItemsCollection()->load();
                
                // Set the quote in session using reflection to ensure it's cached
                $reflection = new \ReflectionClass($subject);
                if ($reflection->hasProperty('_quote')) {
                    $property = $reflection->getProperty('_quote');
                    $property->setAccessible(true);
                    $property->setValue($subject, $sessionQuote);
                }
                
                return $sessionQuote;
            } catch (\Exception $e) {
                // If loading fails, fall back to original method
            }
        }
        
        // Fall back to original method if we couldn't load from repository
        return $proceed();
    }
}

