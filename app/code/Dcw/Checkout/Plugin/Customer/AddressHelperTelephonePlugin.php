<?php

declare(strict_types=1);

namespace Dcw\Checkout\Plugin\Customer;

use Magento\Customer\Helper\Address as AddressHelper;

/**
 * Adds jQuery validation class for minimum 10 digits on address telephone fields.
 */
class AddressHelperTelephonePlugin
{
    private const TELEPHONE = 'telephone';

    public function afterGetAttributeValidationClass(
        AddressHelper $subject,
        string $result,
        string $attributeCode
    ): string {
        if ($attributeCode !== self::TELEPHONE) {
            return $result;
        }

        $extra = 'validate-dcw-phone-min-digits';

        return trim($result) === '' ? $extra : trim($result . ' ' . $extra);
    }
}
