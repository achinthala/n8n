<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model;

use Dcw\ShipRegionAvailability\Api\Data\RegionInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;

/**
 * Resolves ship-region tokens from the PIM-synced EAV attribute (options + product values).
 */
class RegionCodeNormalizer
{
    /** @var array<string, string>|null lowercase token => canonical attribute option value */
    private ?array $valueByKey = null;

    public function __construct(
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * @return list<string> canonical lowercase attribute option values on the child product
     */
    public function resolveForProduct(Product $product): array
    {
        $this->ensureMaps();

        $tokens = [];
        $text = $product->getAttributeText(Config::ATTR_SHIPS_FROM_REGION);
        $tokens = array_merge($tokens, $this->extractTokens($text));

        if ($tokens === []) {
            $raw = $product->getData(Config::ATTR_SHIPS_FROM_REGION);
            $tokens = array_merge($tokens, $this->extractTokens($raw));
        }

        return $this->resolveTokens($tokens);
    }

    /**
     * Maps a ZIP-grid region row to an attribute option value when possible.
     */
    public function normalizeZipRegion(RegionInterface $region): ?string
    {
        $this->ensureMaps();

        foreach ([(string) $region->getCode(), (string) $region->getName()] as $candidate) {
            $candidate = strtolower(trim($candidate));
            if ($candidate !== '' && isset($this->valueByKey[$candidate])) {
                return $this->valueByKey[$candidate];
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function extractTokens(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return [];
        }

        if (is_array($raw)) {
            $parts = [];
            foreach ($raw as $item) {
                $parts = array_merge($parts, $this->extractTokens($item));
            }
            return $parts;
        }

        $string = trim((string) $raw);
        if ($string === '') {
            return [];
        }

        return preg_split('/\s*[,|]\s*/', $string) ?: [];
    }

    /**
     * @param list<string> $tokens
     * @return list<string>
     */
    private function resolveTokens(array $tokens): array
    {
        $this->ensureMaps();

        $values = [];
        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            $canonical = $this->valueByKey[strtolower($token)] ?? null;
            if ($canonical !== null && $canonical !== '') {
                $values[$canonical] = $canonical;
            }
        }

        return array_values($values);
    }

    private function ensureMaps(): void
    {
        if ($this->valueByKey !== null) {
            return;
        }

        $this->valueByKey = [];

        try {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, Config::ATTR_SHIPS_FROM_REGION);
            if (!$attribute || !$attribute->getId() || !$attribute->usesSource()) {
                return;
            }

            foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                $value = trim((string) ($option['value'] ?? ''));
                $label = trim((string) ($option['label'] ?? ''));
                if ($value === '') {
                    continue;
                }

                $canonical = $this->resolveOptionCanonical($value, $label);
                if ($canonical === '') {
                    continue;
                }
                $this->registerToken($value, $canonical);
                foreach (preg_split('/\s*[,|]\s*/', $value) ?: [] as $part) {
                    $part = trim((string) $part);
                    if ($part !== '') {
                        $this->registerToken($part, $canonical);
                    }
                }
                if ($label !== '') {
                    $this->registerToken($label, $canonical);
                    foreach (preg_split('/\s*[,|]\s*/', $label) ?: [] as $part) {
                        $part = trim((string) $part);
                        if ($part !== '') {
                            $this->registerToken($part, $canonical);
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Attribute may not exist in some environments.
        }
    }

    private function resolveOptionCanonical(string $value, string $label): string
    {
        $source = $label !== '' && (ctype_digit($value) || !preg_match('/[a-z]/i', $value))
            ? $label
            : $value;

        return $this->extractCanonicalRegionCode($source);
    }

    private function extractCanonicalRegionCode(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $parts = preg_split('/\s*[,|]\s*/', $value) ?: [];
        $first = strtolower(trim((string) ($parts[0] ?? '')));
        if ($first !== '') {
            return $first;
        }

        return strtolower($value);
    }

    private function registerToken(string $token, string $canonical): void
    {
        $this->valueByKey[$token] = $canonical;
        $this->valueByKey[strtolower($token)] = $canonical;
        if (ctype_digit($token)) {
            $this->valueByKey[(string) (int) $token] = $canonical;
        }
    }
}
