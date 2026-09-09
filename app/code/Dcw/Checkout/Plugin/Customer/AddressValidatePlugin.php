<?php

declare(strict_types=1);

namespace Dcw\Checkout\Plugin\Customer;

use Dcw\Checkout\Model\Validator\TelephoneDigitsValidator;
use Magento\Customer\Model\Address;

/**
 * Server-side telephone check for customer account addresses (add/edit).
 */
class AddressValidatePlugin
{
    public function __construct(
        private readonly TelephoneDigitsValidator $telephoneDigitsValidator
    ) {
    }

    /**
     * @param bool|array $result
     * @return bool|array
     */
    public function afterValidate(Address $subject, $result)
    {
        if ($subject->getShouldIgnoreValidation()) {
            return $result;
        }

        $phrase = $this->telephoneDigitsValidator->getValidationError($subject->getTelephone());
        if ($phrase === null) {
            return $result;
        }

        if ($result === true) {
            return [$phrase];
        }

        if (is_array($result)) {
            $result[] = $phrase;

            return $result;
        }

        return $result;
    }
}
