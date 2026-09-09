<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\OrderRestriction;

use Dotcomweavers\OrderRestrictions\Model\ResourceModel\Rule\CollectionFactory as RulesCollectionFactory;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShippingViewModelData;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Validates only Order Restriction rules with apply_type = restriction (blocking).
 *
 * Used by CheckRestriction (early AJAX) and place order (final guard). Same rule set and messages.
 * apply_type = pending_review is not evaluated here; those rules are handled after place order
 * (e.g. OrderRestrictionPendingReviewMatcher + ApplyPendingReviewAfterPlaceOrder).
 */
class QuoteRulesValidator
{
    public const XML_PATH_LOGIN_AS_CUSTOMER_FLAG = 'orderrestrictions/general/enable_admin_login_as_customer';

    private const APPLY_TYPE_RESTRICTION = 'restriction';

    public function __construct(
        private readonly RulesCollectionFactory $rulesCollectionFactory,
        private readonly IncstoreShippingViewModelData $incstoreShippingViewModelData,
        private readonly Session $customerSession,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @throws LocalizedException when any enabled restriction rule matches (same behaviour as CheckRestriction)
     */
    public function assertQuotePassesBlockingRules(Quote $quote, OrderInterface $order): void
    {
        if ($this->shouldSkipForLoginAsCustomer()) {
            return;
        }

        $rules = $this->loadEnabledRestrictionRules((int) $order->getStoreId(), (int) $order->getCustomerGroupId());
        $result = $this->evaluateRulesAgainstQuote($quote, $rules);

        if ($result['messages'] !== []) {
            throw new LocalizedException(__('%1', implode(' | ', $result['messages'])));
        }
    }

    /**
     * Early checkout validation (e.g. shipping step AJAX).
     *
     * @return array{messages: string[], rule_names: string[]}
     */
    public function collectBlockingRuleViolations(Quote $quote): array
    {
        if ($this->shouldSkipForLoginAsCustomer()) {
            return ['messages' => [], 'rule_names' => []];
        }

        $rules = $this->loadEnabledRestrictionRules((int) $quote->getStoreId(), (int) $quote->getCustomerGroupId());

        return $this->evaluateRulesAgainstQuote($quote, $rules);
    }

    /**
     * @param \Dotcomweavers\OrderRestrictions\Model\Rule[] $rules
     * @return array{messages: string[], rule_names: string[]}
     */
    private function evaluateRulesAgainstQuote(Quote $quote, array $rules): array
    {
        $items = $quote->getAllItems();

        $totalWeight = 0.0;
        foreach ($quote->getAllVisibleItems() as $item) {
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

        $messages = [];
        $ruleNames = [];
        foreach ($rules as $rule) {
            if ($rule->getConditions()->validate($address, $items) == '1') {
                $messages[] = (string) $rule->getMessage();
                $ruleNames[] = strtolower((string) $rule->getName());
            }
        }

        return ['messages' => $messages, 'rule_names' => $ruleNames];
    }

    /**
     * @return \Dotcomweavers\OrderRestrictions\Model\Rule[]
     */
    private function loadEnabledRestrictionRules(int $storeId, int $customerGroupId): array
    {
        $groupId = (string) (int) $customerGroupId;

        $rulesCollection = $this->rulesCollectionFactory->create();
        $rulesCollection->addFieldToFilter('stores', [['finset' => '0'], ['finset' => $storeId]]);
        $rulesCollection->addFieldToFilter('customer_groups', ['finset' => explode(',', $groupId)]);
        $rulesCollection->addFieldToFilter('enabled', 1);
        $rulesCollection->addFieldToFilter('apply_type', self::APPLY_TYPE_RESTRICTION);

        return $rulesCollection->getItems();
    }

    private function shouldSkipForLoginAsCustomer(): bool
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }
        $cdata = $this->customerSession->getData();
        $customerFlag = $this->scopeConfig->getValue(self::XML_PATH_LOGIN_AS_CUSTOMER_FLAG);

        return isset($cdata['logged_as_customer_admind_id']) && $customerFlag;
    }
}
