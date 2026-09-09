<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Model\Order;

use Magento\Sales\Api\Data\OrderAddressInterface;

class AddressFormatter
{
    public function format(OrderAddressInterface $address): string
    {
        $street = $address->getStreet();
        $streetLines = is_array($street) ? implode(', ', array_filter($street)) : (string) $street;

        $lines = array_filter([
            $address->getCompany(),
            trim(implode(' ', array_filter([$address->getFirstname(), $address->getLastname()]))),
            $streetLines,
            trim(implode(', ', array_filter([
                $address->getCity(),
                $address->getRegion(),
                $address->getPostcode(),
            ]))),
            $address->getCountryId(),
            $address->getTelephone() ? 'T: ' . $address->getTelephone() : null,
        ]);

        return implode("\n", $lines);
    }
}
