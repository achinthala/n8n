<?php
declare(strict_types=1);

namespace Dcw\IncstoreShipping\Model\Quote\Address\Total;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\FreeShippingInterface;
use Magento\Quote\Model\Quote\Address\Total as AddressTotal;

class Shipping extends \Magento\Quote\Model\Quote\Address\Total\Shipping
{
    private const METHOD_CODE = 'incstoreshipping_incstoreshipping';

    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        FreeShippingInterface $freeShipping,
        private readonly CheckoutSession $checkoutSession
    ) {
        parent::__construct($priceCurrency, $freeShipping);
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        AddressTotal $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $shipping = $shippingAssignment->getShipping();
        if (!$shipping || $shipping->getMethod() !== self::METHOD_CODE) {
            return $this;
        }

        $address = $shipping->getAddress();
        $addressAmount = $address ? (float) $address->getShippingAmount() : null;
        $amount = $this->resolveShippingAmount($addressAmount);
        if ($amount === null) {
            return $this;
        }

        $store = $quote->getStore();
        $convertedAmount = $this->priceCurrency->convert($amount, $store);

        $total->setTotalAmount($this->getCode(), $convertedAmount);
        $total->setBaseTotalAmount($this->getCode(), $amount);
        $total->setShippingAmount($convertedAmount);
        $total->setBaseShippingAmount($amount);
        $total->setShippingDescription($address?->getShippingDescription());

        if ($address) {
            $address->setShippingAmount($convertedAmount);
            $address->setBaseShippingAmount($amount);
            $address->setShippingInclTax($convertedAmount);
            $address->setBaseShippingInclTax($amount);
        }

        return $this;
    }

    private function resolveShippingAmount(?float $fallbackAmount): ?float
    {
        $apiData = $this->checkoutSession->getShippingApiDataRaw();
        if (is_array($apiData) && isset($apiData['charge'])) {
            return (float) $apiData['charge'];
        }

        if ($fallbackAmount !== null && $fallbackAmount > 0) {
            return (float) $fallbackAmount;
        }

        return null;
    }
}

