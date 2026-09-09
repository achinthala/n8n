<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Block\Account\Quote;

use Dcw\RequestQuote\Api\QuoteChangeLogRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;
use Amasty\RequestQuote\Model\QuoteRepository;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Block to display quote change logs
 */
class ChangeLog extends Template
{
    /**
     * @param Template\Context $context
     * @param QuoteChangeLogRepositoryInterface $changeLogRepository
     * @param Json $json
     * @param ScopeConfigInterface $scopeConfig
     * @param Registry $registry
     * @param RequestInterface $request
     * @param QuoteRepository $quoteRepository
     * @param PriceHelper $priceHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly QuoteChangeLogRepositoryInterface $changeLogRepository,
        private readonly Json $json,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Registry $registry,
        private readonly RequestInterface $request,
        private readonly QuoteRepository $quoteRepository,
        private readonly PriceHelper $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    
    /**
     * Check if change logs should be shown to customer
     *
     * @return bool
     */
    public function isShowToCustomer(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'requestquote/change_log/show_to_customer',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get quote change logs
     *
     * @return \Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface[]
     */
    public function getChangeLogs(): array
    {
        $quote = $this->getQuote();
        if (!$quote || !$quote->getId()) {
            return [];
        }

        try {
            return $this->changeLogRepository->getByQuoteId((int)$quote->getId());
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get formatted action label
     *
     * @param string $actionType
     * @return string
     */
    public function getActionLabel(string $actionType): string
    {
        $labels = [
            'item_added' => __('Item Added'),
            'item_removed' => __('Item Removed'),
            'item_updated' => __('Item Updated'),
            'items_merged' => __('Items Merged'),
            'discount_changed' => __('Discount Changed'),
            'discount_carried_over' => __('Discount Carried Over'),
            'custom_fee_changed' => __('Shipping Amount Changed'),
            'status_changed' => __('Status Changed'),
            'quote_edited' => __('Quote Edited'),
        ];

        $label = $labels[$actionType] ?? ucwords(str_replace('_', ' ', $actionType));
        return $label instanceof \Magento\Framework\Phrase ? $label->render() : (string)$label;
    }

    /**
     * Format change log data for display
     *
     * @param \Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface $log
     * @return array
     */
    public function formatLogData(\Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface $log): array
    {
        $oldValue = null;
        $newValue = null;

        if ($log->getOldValue()) {
            try {
                $oldValue = $this->json->unserialize($log->getOldValue());
            } catch (\Exception $e) {
                $oldValue = $log->getOldValue();
            }
        }

        if ($log->getNewValue()) {
            try {
                $newValue = $this->json->unserialize($log->getNewValue());
            } catch (\Exception $e) {
                $newValue = $log->getNewValue();
            }
        }

        return [
            'action' => $this->getActionLabel($log->getActionType()),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => $log->getChangedBy(),
            'created_at' => $log->getCreatedAt(),
            'item_id' => $log->getItemId(),
        ];
    }

    /**
     * Get quote from parent block or registry
     *
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    public function getQuote()
    {
        // Try to get from parent block first
        $parentBlock = $this->getParentBlock();
        if ($parentBlock && method_exists($parentBlock, 'getQuote')) {
            return $parentBlock->getQuote();
        }
        
        // Try to get from layout - check all Info blocks
        $layout = $this->getLayout();
        if ($layout) {
            $infoBlocks = ['quote.comments', 'quote.status', 'quote.date'];
            foreach ($infoBlocks as $blockName) {
                $infoBlock = $layout->getBlock($blockName);
                if ($infoBlock && method_exists($infoBlock, 'getQuote')) {
                    $quote = $infoBlock->getQuote();
                    if ($quote && $quote->getId()) {
                        return $quote;
                    }
                }
            }
        }
        
        // Try to get from registry
        $quote = $this->registry->registry('current_amasty_quote');
        if ($quote) {
            return $quote;
        }
        
        // Try to get quote ID from request and load it
        $quoteId = (int)$this->request->getParam('quote_id');
        if ($quoteId) {
            try {
                return $this->quoteRepository->get($quoteId);
            } catch (\Exception $e) {
                // Quote not found
            }
        }
        
        return null;
    }
    
    /**
     * Format date (override parent to use MEDIUM format by default)
     *
     * @param string|null $date
     * @param int $format
     * @param bool $showTime
     * @param string|null $timezone
     * @return string
     */
    public function formatDate($date = null, $format = \IntlDateFormatter::MEDIUM, $showTime = false, $timezone = null)
    {
        if (!$date) {
            return '';
        }
        
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : \IntlDateFormatter::NONE,
            $timezone
        );
    }
    
    /**
     * Format price
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }
}

