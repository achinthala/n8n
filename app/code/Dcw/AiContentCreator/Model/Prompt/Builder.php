<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Prompt;

use Dcw\AiContentCreator\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Builds generation/rewrite prompts from product context and configurable instructions.
 */
class Builder
{
    public const MODE_GENERATE = 'generate';
    public const MODE_REWRITE = 'rewrite';

    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @param array<string, string> $currentValues Current form/attribute values keyed by attribute code
     */
    public function build(
        ProductInterface $product,
        string $attributeCode,
        string $mode,
        array $currentValues = [],
        string $extraInstructions = '',
        ?int $storeId = null
    ): string {
        $parts = [];

        $brandVoice = $this->config->getBrandVoice($storeId);
        if ($brandVoice !== '') {
            $parts[] = "Brand voice / global instructions:\n" . $brandVoice;
        }

        $fieldPrompt = $this->config->getFieldPrompt($attributeCode, $storeId);
        if ($fieldPrompt !== '') {
            $parts[] = "Field-specific instructions for {$attributeCode}:\n" . $fieldPrompt;
        }

        if (trim($extraInstructions) !== '') {
            $parts[] = "Additional editor instructions:\n" . trim($extraInstructions);
        }

        $parts[] = "Product context:\n"
            . '- Name: ' . (string) $product->getName() . "\n"
            . '- SKU: ' . (string) $product->getSku() . "\n"
            . '- Attribute set / type: ' . (string) $product->getTypeId();

        $mode = $mode === self::MODE_REWRITE ? self::MODE_REWRITE : self::MODE_GENERATE;
        $existing = (string) ($currentValues[$attributeCode] ?? $product->getData($attributeCode) ?? '');

        if ($mode === self::MODE_REWRITE) {
            $parts[] = "Mode: Rewrite/Improve the existing {$attributeCode} content below. Preserve factual accuracy; improve clarity, SEO usefulness, and brand voice.";
            $parts[] = "Existing {$attributeCode} content:\n" . ($existing !== '' ? $existing : '[empty]');
        } else {
            $parts[] = "Mode: Generate new {$attributeCode} content for this product.";
            if ($existing !== '') {
                $parts[] = "Existing {$attributeCode} content (for reference only; produce a fresh draft):\n" . $existing;
            }
        }

        // Include sibling marketing fields as light context (not as write targets).
        foreach (Config::ALLOWED_ATTRIBUTES as $code) {
            if ($code === $attributeCode) {
                continue;
            }
            $value = (string) ($currentValues[$code] ?? $product->getData($code) ?? '');
            if ($value !== '') {
                $parts[] = "Related field {$code} (context only):\n" . mb_substr(strip_tags($value), 0, 800);
            }
        }

        $parts[] = "Return ONLY the final {$attributeCode} content. Do not include labels, explanations, or markdown code fences.";

        return implode("\n\n", $parts);
    }
}
