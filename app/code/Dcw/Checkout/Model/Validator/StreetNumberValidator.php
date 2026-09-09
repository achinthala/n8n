<?php

declare(strict_types=1);

namespace Dcw\Checkout\Model\Validator;

use Magento\Framework\Exception\InputException;

class StreetNumberValidator
{
    public function validate(array $street): void
    {
        $streetValue = implode(' ', $street);

        // Address must contain at least one digit
        if (!preg_match('/\d/', $streetValue)) {
            throw new InputException(
                __('Invalid Address. Please contact us if this error persists.')
            );
        }
    }
}