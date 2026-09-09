<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\Integration;

use Dotcomweavers\OrderRestrictions\Model\ResourceModel\Rule\CollectionFactory as RulesCollectionFactory;
use Dotcomweavers\OrderRestrictions\Model\Rule;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShippingViewModelData;
use Dcw\OrderPendingReview\Model\PendingReviewReason;
use Dcw\OrderPendingReview\Model\Quote\QuoteFraudFlagCalculator;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Quote/address/condition validation for rules with apply_type = pending_review.
 * Runs even when the order was placed via Login as Customer (blocking rules may still be skipped elsewhere).
 */
class OrderRestrictionPendingReviewMatcher
{
    public function __construct(
        private readonly RulesCollectionFactory $rulesCollectionFactory,
        private readonly QuoteFactory $quoteFactory,
        private readonly IncstoreShippingViewModelData $incstoreShippingViewModelData,
        private readonly QuoteFraudFlagCalculator $quoteFraudFlagCalculator
    ) {
    }

    /**
     * Each matched rule becomes one payload for {@see \Dcw\OrderPendingReview\Model\HoldReasonCollector}.
     *
     * @return list<array<string, mixed>>
     */
    public function getMatchingRules(OrderInterface $order): array
    {
        $quoteId = $order->getQuoteId();
        if (!$quoteId) {
            return [];
        }

        $quote = $this->quoteFactory->create()->load((int) $quoteId);
        if (!$quote->getId()) {
            return [];
        }

        if ($order->getPayment() && $order->getPayment()->getMethod()) {
            $quote->getPayment()->setMethod($order->getPayment()->getMethod());
        }

        $items = $quote->getAllItems();
        $rules = $this->getPendingReviewRules($order);

        $allVisibleItems = $quote->getAllVisibleItems();
        $totalWeight = 0.0;
        foreach ($allVisibleItems as $item) {
            if ($item->getWeight()) {
                $unitWeight = (float) $item->getWeight();
                $unitWeightCal = $this->incstoreShippingViewModelData->calculateUnitWeight($item);
                if ($unitWeightCal) {
                    $unitWeight = (float) $unitWeightCal;
                }
                $totalWeight += $item->getQty() * $unitWeight;
            }
        }

        $address = $quote->getIsVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $address->setData('total_qty', $quote->getItemsQty());
        $address->setData('weight', $totalWeight);

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->quoteFraudFlagCalculator->apply($quote);

        $matched = [];
        foreach ($rules as $rule) {
            if ($rule->getConditions()->validate($address, $items) == '1') {
                $matched[] = $this->ruleToPayload($rule);
            }
        }

        return $matched;
    }

    /**
     * @param Rule $rule
     * @return array<string, mixed>
     */
    private function ruleToPayload(Rule $rule): array
    {
        $name = trim((string) $rule->getName());
        $internalNotes = trim((string) $rule->getData('internal_notes'));
        $message = trim((string) $rule->getData('message'));
        $title = $internalNotes !== '' ? $internalNotes : $message;

        return [
            PendingReviewReason::FIELD_TYPE => PendingReviewReason::TYPE_ORDER_RESTRICTION,
            'restriction_id' => (int) $rule->getId(),
            'name' => $name,
            'internal_notes' => $internalNotes,
            'message' => $message,
            'title' => $title,
        ];
    }

    /**
     * Use the order's store and customer group so rules match after place order (session can differ).
     *
     * @return Rule[]
     */
    private function getPendingReviewRules(OrderInterface $order): array
    {
        $currentStoreId = (int) $order->getStoreId();
        $groupId = (string) (int) $order->getCustomerGroupId();

        $rulesCollection = $this->rulesCollectionFactory->create();
        $rulesCollection->addFieldToFilter('stores', [['finset' => '0'], ['finset' => $currentStoreId]]);
        $rulesCollection->addFieldToFilter('customer_groups', ['finset' => explode(',', $groupId)]);
        $rulesCollection->addFieldToFilter('enabled', 1);
        $rulesCollection->addFieldToFilter('apply_type', 'pending_review');

        return $rulesCollection->getItems();
    }
}
