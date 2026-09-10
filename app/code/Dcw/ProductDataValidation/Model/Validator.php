<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

use Magento\Catalog\Model\Product;

/**
 * Validates a single product against configured required attributes.
 */
class Validator
{
    private const IMAGE_ATTRIBUTES = ['image', 'small_image', 'thumbnail', 'swatch_image'];

    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Return list of missing/invalid attribute codes for the product.
     *
     * @param string[] $attributeCodes
     * @return string[]
     */
    public function getMissingAttributes(Product $product, array $attributeCodes): array
    {
        $missing = [];
        $htmlAttributes = $this->config->getHtmlAttributes();

        foreach ($attributeCodes as $code) {
            if ($this->isMissing($product, $code, $htmlAttributes)) {
                $missing[] = $code;
            }
        }

        return $missing;
    }

    /**
     * @param string[] $htmlAttributes
     */
    private function isMissing(Product $product, string $code, array $htmlAttributes): bool
    {
        if ($code === 'price') {
            return $this->isPriceInvalid($product);
        }

        if (in_array($code, self::IMAGE_ATTRIBUTES, true)) {
            return $this->isImageMissing($product, $code);
        }

        $value = $product->getData($code);

        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        $stringValue = is_scalar($value) ? (string) $value : '';

        if (in_array($code, $htmlAttributes, true)) {
            $stringValue = $this->normalizeHtml($stringValue);
        }

        return trim($stringValue) === '';
    }

    private function isPriceInvalid(Product $product): bool
    {
        $price = $product->getData('price');
        if ($price === null || $price === '') {
            return true;
        }

        return (float) $price <= 0.0;
    }

    private function isImageMissing(Product $product, string $code): bool
    {
        $value = (string) $product->getData($code);
        $value = trim($value);

        return $value === '' || $value === 'no_selection';
    }

    private function normalizeHtml(string $value): string
    {
        $stripped = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Replace non-breaking spaces and other whitespace with standard space.
        $stripped = preg_replace('/\x{00A0}|\s+/u', ' ', $stripped) ?? '';

        return trim($stripped);
    }
}
