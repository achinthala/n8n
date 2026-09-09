<?php
declare(strict_types=1);

namespace Dcw\CheckoutExtraField\Model\Quote;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Magento Checkout Staging array_diff()s quote/billing getData().
 * Quote AddressExtension cannot be converted to string (PHP Error).
 */
class AddressExtensionObjectStripper
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
    }

    public function stripFromCartAndBilling(string|int $cartId, ?AddressInterface $billingAddress): void
    {
        $this->strip($billingAddress);

        $quoteId = $this->resolveQuoteEntityId($cartId);
        if ($quoteId <= 0) {
            return;
        }

        try {
            $quote = $this->cartRepository->getActive($quoteId);
        } catch (NoSuchEntityException) {
            return;
        }

        $this->strip($quote->getShippingAddress());
        $this->strip($quote->getBillingAddress());
    }

    public function strip(?AddressInterface $address): void
    {
        if (!$address || !method_exists($address, 'getData') || !method_exists($address, 'unsetData')) {
            return;
        }

        $extension = $address->getData(ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY);
        if (is_object($extension)) {
            $address->unsetData(ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY);
        }
    }

    private function resolveQuoteEntityId(string|int $cartId): int
    {
        $id = (string) $cartId;
        if ($id !== '' && ctype_digit($id)) {
            return (int) $id;
        }
        if ($id === '') {
            return 0;
        }

        try {
            return (int) $this->maskedQuoteIdToQuoteId->execute($id);
        } catch (NoSuchEntityException) {
            return 0;
        }
    }
}
