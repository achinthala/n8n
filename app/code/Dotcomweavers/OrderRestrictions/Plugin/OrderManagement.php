<?php
/**
 * OrderRestrictions Plugin.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */

namespace Dotcomweavers\OrderRestrictions\Plugin;

use Dcw\OrderPendingReview\Model\OrderRestriction\QuoteRulesValidator;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Quote\Model\QuoteFactory;

/**
 * Delegates blocking rule validation to Dcw_OrderPendingReview\QuoteRulesValidator (single source of truth).
 */
class OrderManagement
{
    /**
     * @param QuoteFactory $quoteFactory
     * @param QuoteRulesValidator $quoteRulesValidator
     */
    public function __construct(
        protected QuoteFactory $quoteFactory,
        private readonly QuoteRulesValidator $quoteRulesValidator
    ) {
    }

    /**
     * Plugin before order place
     *
     * @param OrderManagementInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePlace(
        OrderManagementInterface $subject,
        OrderInterface $order
    ): array {
        $quoteId = $order->getQuoteId();
        if (!$quoteId) {
            return [$order];
        }

        $quote = $this->quoteFactory->create()->load((int) $quoteId);
        if (!$quote->getId()) {
            return [$order];
        }

        $this->quoteRulesValidator->assertQuotePassesBlockingRules($quote, $order);

        return [$order];
    }
}
