<?php

declare(strict_types=1);

namespace Dotcomweavers\OrderRestrictions\Controller\Index;

use Dcw\IncstoreShipping\Logger\Logger as IncstoreShippingLogger;
use Dcw\OrderPendingReview\Model\OrderRestriction\QuoteRulesValidator;
use Dcw\OrderPendingReview\Model\Quote\QuoteFraudFlagCalculator;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class CheckRestriction extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const XML_PATH_CUSTOMER_FLAG = 'orderrestrictions/general/enable_admin_login_as_customer';

    private const CUSTOMER_ADDRESS_NEW = 'new-customer-address';

    private const CUSTOMER_ADDRESS_UNDEFINED = 'undefined';

    private const CUSTOMER_ADDRESS_AMASTY_QUOTE = 'amasty_quote_address';

    private const RULE_RESULT_BLOCKED = '1';

    private const RULE_RESULT_API_FAIL = 2;

    private const LOG_PREFIX = 'OrderRestrictions-CheckRestriction';

    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Cart $cart,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly RegionFactory $regionFactory,
        private readonly CheckoutSession $checkoutSession,
        private readonly QuoteRulesValidator $quoteRulesValidator,
        private readonly QuoteFraudFlagCalculator $quoteFraudFlagCalculator,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly IncstoreShippingLogger $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): Json
    {
        $quoteId = (int) $this->cart->getQuote()->getId();
        $post = $this->getRequest()->getPostValue() ?? [];

        $ruleResult = '';
        $ruleResultMsg = '';
        $ruleName = '';

        if (!$quoteId) {
            return $this->createJsonResult($ruleResult, $ruleResultMsg, $ruleName);
        }

        if ($this->isAdminLoggedInAsCustomer()) {
            $ruleResult = $this->calculateShippingAdmin($quoteId, $post) ? '' : self::RULE_RESULT_API_FAIL;

            return $this->createJsonResult($ruleResult, $ruleResultMsg, $ruleName);
        }

        $quote = $this->loadQuote($quoteId);
        if (!$quote) {
            return $this->createJsonResult($ruleResult, $ruleResultMsg, $ruleName);
        }

        $this->applyPostedShippingAddress($quote, $post);
        $this->setShippingDataFromCheckoutSession($this->getQuoteAddress($quote));

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        $this->quoteFraudFlagCalculator->apply($quote);
        $this->cartRepository->save($quote);

        $violation = $this->quoteRulesValidator->collectBlockingRuleViolations($quote);
        if ($violation['messages'] !== []) {
            $ruleResult = self::RULE_RESULT_BLOCKED;
            $ruleResultMsg = implode(' | ', $violation['messages']);
            $ruleName = implode(', ', $violation['rule_names']);
        }

        if ($ruleResult === self::RULE_RESULT_BLOCKED) {
            $this->checkoutSession->setOrderRestrictionReset(1);
        } else {
            $this->checkoutSession->setIsFromCheckRestriction(1);
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();

            if ((int) $this->checkoutSession->getApiFailResponseCheck() === 1) {
                $ruleResult = self::RULE_RESULT_API_FAIL;
            }

            if ($this->checkoutSession->getOrderRestrictionReset()) {
                $this->checkoutSession->unsOrderRestrictionReset();
            }
        }

        return $this->createJsonResult($ruleResult, $ruleResultMsg, $ruleName);
    }

    /**
     * Calculate shipping when an admin is logged in as a customer.
     */
    private function calculateShippingAdmin(int $quoteId, array $post): bool
    {
        $quote = $this->loadQuote($quoteId);
        if (!$quote) {
            return false;
        }

        $this->applyPostedShippingAddress($quote, $post);
        $address = $this->getQuoteAddress($quote);
        $address->setData('total_qty', $quote->getItemsQty());

        $this->setShippingDataFromCheckoutSession($address);

        $this->checkoutSession->setIsFromCheckRestriction(1);
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        return (int) $this->checkoutSession->getApiFailResponse() !== 1;
    }

    /**
     * Clear cached shipping data when the quote address no longer matches the session.
     */
    private function setShippingDataFromCheckoutSession(QuoteAddress $address): void
    {
        $newAddressData = $this->buildAddressDataFromQuoteAddress($address);

        $this->logger->info(
            self::LOG_PREFIX . '-setShippingDataFromCheckoutSession quote address: '
            . json_encode($newAddressData)
        );

        $existingData = $this->checkoutSession->getShippingCustomDataRaw();
        if (!is_array($existingData)) {
            $existingData = $this->checkoutSession->getShippingApiDataRaw();
        }

        if (!is_array($existingData)) {
            $this->logger->info(
                self::LOG_PREFIX . '-setShippingDataFromCheckoutSession: no existing shipping session data'
            );
            return;
        }

        if ($this->hasAddressChanged($newAddressData, $existingData)) {
            $this->checkoutSession->setShippingApiDataRaw(null);
            $this->checkoutSession->setShippingCustomDataRaw(null);
            $this->logger->info(
                self::LOG_PREFIX . '-setShippingDataFromCheckoutSession: cleared shipping session due to address change'
            );
            return;
        }

        $this->logger->info(
            self::LOG_PREFIX . '-setShippingDataFromCheckoutSession: address unchanged, keeping session'
        );
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    private function isAdminLoggedInAsCustomer(): bool
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }

        $customerData = $this->customerSession->getData();
        $isEnabled = (bool) $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_FLAG);

        return $isEnabled && isset($customerData['logged_as_customer_admind_id']);
    }

    private function loadQuote(int $quoteId): ?Quote
    {
        try {
            /** @var Quote $quote */
            $quote = $this->cartRepository->get($quoteId);

            return $quote;
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    private function getQuoteAddress(Quote $quote): QuoteAddress
    {
        return $quote->getIsVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
    }

    private function applyPostedShippingAddress(Quote $quote, array $post): void
    {
        $address = $this->getQuoteAddress($quote);

        if ($this->customerSession->isLoggedIn()) {
            $address->setData('email', $this->customerSession->getCustomer()->getEmail());
            $this->applyLoggedInCustomerAddress($address, $post);
            return;
        }

        $this->applyGuestAddress($quote, $address, $post);
    }

    private function applyLoggedInCustomerAddress(QuoteAddress $address, array $post): void
    {
        $customerAddressId = $post['customer_address_id'] ?? '';

        if ($customerAddressId !== '' && $this->isNewCustomerAddress($customerAddressId)) {
            $this->applyManualAddressFromPost($address, $post);
            return;
        }

        if ($customerAddressId !== '') {
            $this->applySavedCustomerAddress($address, $customerAddressId);
        }
    }

    private function applyGuestAddress(Quote $quote, QuoteAddress $address, array $post): void
    {
        $email = $post['username'] ?? $post['newUsername'] ?? '';
        $address->setData('email', $email);

        $quote->setData('customer_email', $email);

        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setData('email', $email);
        $billingAddress->setData('firstname', $post['firstname'] ?? '');
        $billingAddress->setData('lastname', $post['lastname'] ?? '');

        $this->applyManualAddressFromPost($address, $post);
    }

    private function applyManualAddressFromPost(QuoteAddress $address, array $post): void
    {
        $street = $post['street'] ?? [];
        $regionId = (int) ($post['region_id'] ?? 0);

        $address->setData('firstname', $post['firstname'] ?? '');
        $address->setData('lastname', $post['lastname'] ?? '');
        $address->setData('street', is_array($street) ? implode("\r\n", $street) : (string) $street);
        $address->setData('company', $post['company'] ?? '');
        $address->setData('country_id', $post['country_id'] ?? '');
        $address->setData('region_id', $regionId > 0 ? $regionId : null);
        if ($regionId > 0) {
            $address->setData('region', $this->getRegionNameById($regionId));
        } else {
            $address->setData('region', (string) ($post['region'] ?? ''));
        }
        $address->setData('city', $post['city'] ?? '');
        $address->setData('postcode', $post['postcode'] ?? '');
        $address->setData('telephone', $post['telephone'] ?? '');
    }

    private function applySavedCustomerAddress(QuoteAddress $address, string $customerAddressId): void
    {
        $addressId = str_replace('customer-address', '', $customerAddressId);
        $customerAddress = $this->addressRepository->getById($addressId);

        $address->setData('firstname', $customerAddress->getFirstname());
        $address->setData('lastname', $customerAddress->getLastname());
        $address->setData('street', $customerAddress->getStreet());
        $address->setData('company', $customerAddress->getCompany());
        $address->setData('country_id', $customerAddress->getCountryId());
        $address->setData('region_id', $customerAddress->getRegionId());
        $address->setData('region', $customerAddress->getRegion());
        $address->setData('city', $customerAddress->getCity());
        $address->setData('postcode', $customerAddress->getPostcode());
        $address->setData('telephone', $customerAddress->getTelephone());
    }

    private function isNewCustomerAddress(string $customerAddressId): bool
    {
        return in_array($customerAddressId, [
            self::CUSTOMER_ADDRESS_NEW,
            self::CUSTOMER_ADDRESS_UNDEFINED,
            self::CUSTOMER_ADDRESS_AMASTY_QUOTE,
        ], true);
    }

    private function getRegionNameById(int $regionId): string
    {
        if ($regionId <= 0) {
            return '';
        }

        return (string) $this->regionFactory->create()->load($regionId)->getName();
    }

    /**
     * @return array<string, string>
     */
    private function buildAddressDataFromQuoteAddress(QuoteAddress $address): array
    {
        $street = $address->getStreet();
        if (is_array($street)) {
            $street = implode("\n", $street);
        }

        return [
            'dest_postcode' => trim((string) $address->getPostcode()),
            'dest_street' => trim((string) $street),
            'dest_city' => trim((string) $address->getCity()),
            'dest_region_code' => $this->resolveRegionCodeFromAddress($address),
        ];
    }

    private function resolveRegionCodeFromAddress(QuoteAddress $address): string
    {
        $regionId = (int) $address->getRegionId();
        if ($regionId > 0) {
            return strtoupper((string) $this->regionFactory->create()->load($regionId)->getCode());
        }

        $regionCode = trim((string) $address->getRegionCode());
        if ($regionCode === '') {
            $regionCode = trim((string) $address->getRegion());
        }

        return $regionCode !== '' ? strtoupper($regionCode) : '';
    }

    /**
     * @param array<string, string> $newAddressData
     * @param array<string, mixed> $existingData
     */
    private function hasAddressChanged(array $newAddressData, array $existingData): bool
    {
        $existingAddressData = [];
        foreach (array_keys($newAddressData) as $field) {
            $existingAddressData[$field] = isset($existingData[$field])
                ? trim((string) $existingData[$field])
                : '';
        }

        $this->logger->info(
            self::LOG_PREFIX . '-setShippingDataFromCheckoutSession existing address: '
            . json_encode($existingAddressData)
        );

        foreach ($newAddressData as $field => $value) {
            $storedValue = $existingAddressData[$field];
            if ($value !== '' && strcasecmp($value, $storedValue) !== 0) {
                $this->logger->info(
                    self::LOG_PREFIX . '-setShippingDataFromCheckoutSession: address mismatch on '
                    . $field . ' expected: ' . $storedValue . ', got: ' . $value
                );
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|int $ruleResult
     */
    private function createJsonResult($ruleResult, string $ruleResultMsg, string $ruleName): Json
    {
        return $this->resultJsonFactory->create()->setData([
            'rule_result' => $ruleResult,
            'message' => $ruleResultMsg,
            'rule_name' => $ruleName,
        ]);
    }
}
