<?php

declare(strict_types=1);

namespace Dcw\Checkout\Model\Validator;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;

/**
 * Ensures a phone value uses only digits and allowed separators, and contains exactly 10 digits.
 */
class TelephoneDigitsValidator
{
    private const REQUIRED_DIGITS = 10;

    /** Allowed: digits, whitespace, + - ( ) . */
    private const ALLOWED_CHARS_PATTERN = '/^[\d\s+().\-]+$/u';

    /**
     * @throws InputException
     */
    public function assertMinDigits(?string $telephone): void
    {
        $error = $this->getValidationError($telephone);
        if ($error !== null) {
            throw new InputException($error);
        }
    }

    public function getValidationError(?string $telephone): ?Phrase
    {
        if ($telephone === null || trim($telephone) === '') {
            return null;
        }

        $trimmed = trim($telephone);
        if (!preg_match(self::ALLOWED_CHARS_PATTERN, $trimmed)) {
            return __('Please enter a valid 10-digit phone number.');
        }

        $digits = preg_replace('/\D/', '', $trimmed) ?? '';
        if (strlen($digits) !== self::REQUIRED_DIGITS) {
            return __('Please enter a valid 10-digit phone number.');
        }

        return null;
    }
}
