<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Block\Adminhtml\Quote\Edit;

use Dcw\RequestQuote\Api\QuoteChangeLogRepositoryInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Serialize\Serializer\Json;
use Amasty\RequestQuote\Model\Quote\Backend\Session as QuoteSession;
use Amasty\RequestQuote\Model\QuoteRepository;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

/**
 * Block to display quote change logs in admin
 */
class ChangeLog extends Template
{
    /**
     * @param Template\Context $context
     * @param QuoteChangeLogRepositoryInterface $changeLogRepository
     * @param Json $json
     * @param QuoteSession $quoteSession
     * @param QuoteRepository $quoteRepository
     * @param PriceHelper $priceHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly QuoteChangeLogRepositoryInterface $changeLogRepository,
        private readonly Json $json,
        private readonly QuoteSession $quoteSession,
        private readonly QuoteRepository $quoteRepository,
        private readonly PriceHelper $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
            'admin_id' => $log->getAdminId(),
            'admin_email' => $log->getAdminEmail(),
            'admin_role' => $log->getAdminRole(),
        ];
    }

    /**
     * Get quote from session or request
     *
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    public function getQuote()
    {
        try {
            // Use the same method as Info block - get from session
            $quote = $this->quoteSession->getParentQuote();
            if ($quote && $quote->getId()) {
                return $quote;
            }
        } catch (\Exception $e) {
            // Session might not have quote
        }
        
        // Try to get from request
        $quoteId = (int)$this->getRequest()->getParam('quote_id');
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
     * Format date (override parent to use MEDIUM format by default and show time)
     *
     * @param string|null $date
     * @param int $format
     * @param bool $showTime
     * @param string|null $timezone
     * @return string
     */
    public function formatDate($date = null, $format = \IntlDateFormatter::MEDIUM, $showTime = true, $timezone = null)
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

