<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Plugin\Quote;

use Amasty\RequestQuote\Api\Data\QuoteInterface;
use Dcw\RequestQuote\Service\QuoteChangeLogger;
use Magento\Framework\App\RequestInterface;

/**
 * Plugin to log quote changes when saved
 */
class LogQuoteChanges
{
    /**
     * @var array
     */
    private array $originalQuoteData = [];

    /**
     * @param QuoteChangeLogger $changeLogger
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly QuoteChangeLogger $changeLogger,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Store original quote data before save
     *
     * @param \Amasty\RequestQuote\Model\Quote $subject
     * @return array
     */
    public function beforeSave(\Amasty\RequestQuote\Model\Quote $subject): array
    {
        if ($subject->getId()) {
            // Ensure items are loaded before counting
            $subject->getItemsCollection()->load();
            
            // Store original data for comparison
            $this->originalQuoteData[$subject->getId()] = [
                'status' => $subject->getStatus(),
                'subtotal' => $subject->getSubtotal(),
                'grand_total' => $subject->getGrandTotal(),
                'items_count' => count($subject->getAllItems()),
            ];
        }
        
        return [];
    }

    /**
     * Log changes after save
     *
     * @param \Amasty\RequestQuote\Model\Quote $subject
     * @param \Amasty\RequestQuote\Model\Quote $result
     * @return \Amasty\RequestQuote\Model\Quote
     */
    public function afterSave(\Amasty\RequestQuote\Model\Quote $subject, \Amasty\RequestQuote\Model\Quote $result): \Amasty\RequestQuote\Model\Quote
    {
        if (!$result->getId()) {
            return $result;
        }

        $quoteId = (int)$result->getId();
        $originalData = $this->originalQuoteData[$quoteId] ?? null;

        if ($originalData) {
            // Ensure items are loaded to get accurate count
            $result->getItemsCollection()->load();
            
            $newData = [
                'status' => $result->getStatus(),
                'subtotal' => $result->getSubtotal(),
                'grand_total' => $result->getGrandTotal(),
                'items_count' => count($result->getAllItems()),
            ];

            // Check if status changed
            if (isset($originalData['status']) && $originalData['status'] != $newData['status']) {
                $this->changeLogger->logStatusChanged(
                    $quoteId,
                    (int)$originalData['status'],
                    (int)$newData['status']
                );
            }

            // Note: We don't log generic "quote_edited" anymore
            // All changes are logged with specific action types:
            // - status_changed: logged above
            // - item_updated: logged by UpdatePost controller for qty/price changes
            // - item_added: logged by observer when items are added
            // - item_removed: logged by Delete controller when items are removed
            // - items_merged: logged by InQuote controller when cart items are merged

            // Clean up stored data
            unset($this->originalQuoteData[$quoteId]);
        }

        return $result;
    }
}

