<?php

declare(strict_types=1);

namespace Dcw\ShoppingCart\Model;

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Ensures custom_price / UnitPrice never persist as JS NaN/Infinity or other invalid values.
 */
class CustomPriceSanitizer
{
    /**
     * Returns a finite, non-negative unit price; falls back to catalog final price when input is invalid.
     */
    public function resolveUnitPrice(mixed $customPrice, ProductInterface $product): float
    {
        $fallback = max(0.0, (float) $product->getFinalPrice(1));

        if ($customPrice === null || $customPrice === '') {
            return $fallback;
        }

        if (is_string($customPrice)) {
            $t = strtoupper(trim($customPrice));
            if ($t === 'NAN' || $t === 'INF' || $t === '-INF' || $t === '-NAN') {
                return $fallback;
            }
        }

        if (is_numeric($customPrice)) {
            $value = (float) $customPrice;
        } else {
            $filtered = filter_var($customPrice, FILTER_VALIDATE_FLOAT);
            if ($filtered === false) {
                return $fallback;
            }
            $value = (float) $filtered;
        }

        if (!is_finite($value) || $value < 0) {
            return $fallback;
        }

        return $value;
    }

    /**
     * Format for pdp_line_item JSON (consistent with PricePerSqft etc.).
     */
    public function formatUnitPriceForStorage(float $unitPrice): string
    {
        return number_format($unitPrice, 2, '.', '');
    }
}
