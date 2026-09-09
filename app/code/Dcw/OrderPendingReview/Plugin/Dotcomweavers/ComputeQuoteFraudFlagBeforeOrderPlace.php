<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Dotcomweavers;

use Dotcomweavers\OrderRestrictions\Plugin\OrderManagement as DotcomOrderManagementPlugin;
use Dcw\OrderPendingReview\Model\Quote\QuoteFraudFlagCalculator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

/**
 * Ensures quote.dcw_fraud_flag is computed before Order Restrictions rules run on place order.
 */
class ComputeQuoteFraudFlagBeforeOrderPlace
{
    public function __construct(
        private readonly QuoteFactory $quoteFactory,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly QuoteFraudFlagCalculator $quoteFraudFlagCalculator
    ) {
    }

    /**
     * @param DotcomOrderManagementPlugin $subject
     * @return array{0: OrderManagementInterface, 1: OrderInterface}
     */
    public function beforeBeforePlace(
        DotcomOrderManagementPlugin $subject,
        OrderManagementInterface $orderManagement,
        OrderInterface $order
    ): array {
        $quoteId = $order->getQuoteId();
        if (!$quoteId) {
            return [$orderManagement, $order];
        }
        $quote = $this->quoteFactory->create()->load((int) $quoteId);
        if (!$quote->getId()) {
            return [$orderManagement, $order];
        }
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->quoteFraudFlagCalculator->apply($quote);
        $this->cartRepository->save($quote);

        return [$orderManagement, $order];
    }
}
