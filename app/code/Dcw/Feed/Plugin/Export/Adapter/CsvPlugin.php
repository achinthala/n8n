<?php

declare(strict_types=1);

namespace Dcw\Feed\Plugin\Export\Adapter;

use Amasty\Feed\Model\Export\Adapter\Csv;
use Dcw\Feed\Logger\FeedExportLogger;
use Dcw\Feed\Model\Config\Source\SfPricingMode;
use Dcw\Feed\Model\Config\Source\UnitPricingMeasureMode;
use ReflectionProperty;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchData;

/**
 * Replaces exported price values with the calculated square foot price
 * on the Amasty export row before CSV write.
 *
 * Which products are converted is controlled from the admin configuration
 * (Stores > Configuration > DCW Store Config > Product Feed), so square foot
 * pricing can be rolled out or reverted without a code change.
 *
 * Hook point: Amasty\Feed\Model\Export\Adapter\Csv::writeDataRow()
 * Final CSV values are read from $rowData[$fieldKey] inside that method.
 */
class CsvPlugin
{
    private const XML_PATH_SF_PRICING_MODE = 'dcw_feed/sf_pricing/mode';

    private const XML_PATH_SF_PRICING_SKU_WHITELIST = 'dcw_feed/sf_pricing/sku_whitelist';

    private const XML_PATH_UNIT_PRICING_MODE = 'dcw_feed/unit_pricing/mode';

    private const XML_PATH_UNIT_PRICING_SKU_WHITELIST = 'dcw_feed/unit_pricing/sku_whitelist';

    private const XML_PATH_UNIT_PRICING_TARGET_ATTRIBUTE = 'dcw_feed/unit_pricing/target_attribute';

    private const PRICE_MULTIPLIER = 2.0;

    private const FIELD_KEY_PRICE = 'product|price';

    private const FIELD_KEY_SALE_PRICE = 'price|final_price';

    /**
     * @var string[]
     */
    private const PRICE_ATTRIBUTES = [
        'product|price',
        'price|final_price',
        'price|price',
        'price|regular_price',
        'basic|price',
    ];

    public function __construct(
        private readonly FeedExportLogger $feedExportLogger,
		private ProductRepositoryInterface $productRepository,
		private readonly AdvanceSearchData $advanceSearchData,
        private Configurable $configurable,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param array<string, mixed> $rowData
     */
    public function aroundWriteDataRow(Csv $subject, callable $proceed, array &$rowData)
    {
        $csvFields = $this->getCsvFields($subject);
        $sku = $this->resolveSku($rowData);

        $feedRowPriceBefore = $this->getRowPriceValue($rowData, self::FIELD_KEY_PRICE);
        $feedRowSalePriceBefore = $this->getRowPriceValue($rowData, self::FIELD_KEY_SALE_PRICE);
        $originalPrice = $feedRowPriceBefore ?? $feedRowSalePriceBefore;

        $sfPriceApplied = $this->applyPriceMultiplier($rowData, $csvFields,$sku);
        $this->applyUnitPricingMeasure($rowData, $csvFields, $sku, $sfPriceApplied);

        $feedRowPriceAfter = $this->getRowPriceValue($rowData, self::FIELD_KEY_PRICE);
        $feedRowSalePriceAfter = $this->getRowPriceValue($rowData, self::FIELD_KEY_SALE_PRICE);
        $calculatedPrice = $originalPrice !== null ? $originalPrice * self::PRICE_MULTIPLIER : null;

        if ($sku !== '') {
            $this->feedExportLogger->logExportDebug(
                $sku,
                $originalPrice,
                $calculatedPrice,
                $feedRowPriceBefore,
                $feedRowSalePriceBefore,
                $feedRowPriceAfter,
                $feedRowSalePriceAfter
            );
        }

        return $proceed($rowData);
    }

    /**
     * @param array<string, mixed> $rowData
     * @param array<int, array<string, mixed>> $csvFields
     */
    private function applyPriceMultiplier(array &$rowData, array $csvFields,$sku): bool
    {
        $applied = false;
        $mode = $this->getSfPricingMode();
        if ($mode === SfPricingMode::MODE_DISABLED || $sku === '') {
            return $applied;
        }
        $whitelistedParentSkus = $mode === SfPricingMode::MODE_WHITELIST
            ? $this->getWhitelistedParentSkus()
            : [];

        foreach ($csvFields as $field) {
            if (!empty($field['static_text']) || !$this->isPriceField($field)) {
                continue;
            }

            $fieldKey = $this->getFieldKey($field);
            if (!array_key_exists($fieldKey, $rowData)) {
                continue;
            }

            $oldPrice = $this->toFloat($rowData[$fieldKey]);
            if ($oldPrice === null) {
                continue;
            }

            if ($mode === SfPricingMode::MODE_WHITELIST) {
                $parentSku = $this->getParentSkuByChildSku($sku);
                if ($parentSku === null
                    || !in_array($parentSku, $whitelistedParentSkus, true)
                ) {
                    continue;
                }
                $this->createLogs('parentSku: ' . $parentSku. ' childsku: '.$sku);
            }

            $priceType='final';
            if ($this->isPrice($field)) {
                $priceType='regular';
            }
            if ($this->isFinalPrice($field)) {
                $priceType='final';
            }

            try {
                $productId = $this->productRepository->get($sku)->getId();
                $productCalculatedPrice = $this->advanceSearchData->getCalculatedPrice($productId, $priceType);
            } catch (\Exception $e) {
                // A row must never break the whole feed export
                $this->createLogs('sf pricing skipped for sku ' . $sku . ': ' . $e->getMessage());
                continue;
            }
            // In "all products" mode only rows with an actual square foot style
            // calculation are touched; "each" products keep their original price
            if ($mode === SfPricingMode::MODE_ALL
                && (!is_array($productCalculatedPrice)
                    || ($productCalculatedPrice['calType'] ?? 'each') === 'each')
            ) {
                continue;
            }
            $productCalculatedPriceValue = $this->extractCalculatedPriceValue($productCalculatedPrice);
            if($productCalculatedPriceValue){
                $rowData[$fieldKey] = $productCalculatedPriceValue;
                $applied = true;
            }
        }

        return $applied;
    }

    private function getSfPricingMode(): string
    {
        $mode = (string) $this->scopeConfig->getValue(self::XML_PATH_SF_PRICING_MODE);
        $validModes = [SfPricingMode::MODE_DISABLED, SfPricingMode::MODE_WHITELIST, SfPricingMode::MODE_ALL];

        // Unknown values fall back to the safest mode instead of converting everything
        return in_array($mode, $validModes, true) ? $mode : SfPricingMode::MODE_WHITELIST;
    }

    /**
     * @return string[]
     */
    private function getWhitelistedParentSkus(): array
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_PATH_SF_PRICING_SKU_WHITELIST);

        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            static fn ($value) => $value !== ''
        ));
    }

    /**
     * Writes the square foot area covered by the row price into the feed column
     * mapped to the configured target attribute, so Google can derive and show
     * a per square foot price (price divided by measure) next to the real price.
     *
     * The column is treated as owned by this feature: rows out of scope are
     * emptied so raw values of the target attribute never leak into the feed.
     *
     * @param array<string, mixed> $rowData
     * @param array<int, array<string, mixed>> $csvFields
     */
    private function applyUnitPricingMeasure(array &$rowData, array $csvFields, $sku, bool $sfPriceApplied): void
    {
        $targetAttribute = trim((string) $this->scopeConfig->getValue(self::XML_PATH_UNIT_PRICING_TARGET_ATTRIBUTE));
        if ($targetAttribute === '') {
            return;
        }

        foreach ($csvFields as $field) {
            if (!empty($field['static_text'])
                || (string) ($field['attribute'] ?? '') !== $targetAttribute
            ) {
                continue;
            }

            // The key is created even when absent: Amasty omits row keys for
            // attributes with no value, and the column must still be filled
            $fieldKey = $this->getFieldKey($field);
            $rowData[$fieldKey] = $this->resolveUnitPricingMeasure($sku, $sfPriceApplied);
        }
    }

    private function resolveUnitPricingMeasure($sku, bool $sfPriceApplied): string
    {
        $mode = $this->getUnitPricingMode();
        if ($mode === UnitPricingMeasureMode::MODE_DISABLED || $sku === '') {
            return '';
        }

        if ($mode === UnitPricingMeasureMode::MODE_WHITELIST) {
            $parentSku = $this->getParentSkuByChildSku($sku);
            if ($parentSku === null
                || !in_array($parentSku, $this->getUnitPricingWhitelistedParentSkus(), true)
            ) {
                return '';
            }
        }

        // A row converted to square foot pricing covers exactly one square foot
        if ($sfPriceApplied) {
            return '1sqft';
        }

        try {
            $product = $this->productRepository->get($sku);
        } catch (\Exception $e) {
            return '';
        }

        // Mirror the site price calculation: for case and pre cut roll products the
        // price covers the coverage area of the whole unit, exact dimensions there
        // describe a single piece; for cut-length products the price covers the
        // exact width by exact length slice.
        $floorCalculator = (string) $product->getAttributeText('incstores_pim_calculator_type');
        $coverage = $product->getIncstoresPimCoverage();
        $hasCoverage = is_numeric($coverage) && (float) $coverage > 0;

        if (in_array($floorCalculator, ['case', 'pre_cut_roll'], true) && $hasCoverage) {
            return $this->formatSquareFeet((float) $coverage);
        }

        $width = $product->getData('incstores_pim_exact_width_inches');
        $length = $product->getData('incstores_pim_exact_length_inches');
        if (is_numeric($width) && is_numeric($length)
            && (float) $width > 0 && (float) $length > 0
        ) {
            return $this->formatSquareFeet((float) $width * (float) $length / 144);
        }

        if ($hasCoverage) {
            return $this->formatSquareFeet((float) $coverage);
        }

        return '';
    }

    private function formatSquareFeet(float $area): string
    {
        $formatted = rtrim(rtrim(number_format($area, 2, '.', ''), '0'), '.');

        return $formatted . 'sqft';
    }

    private function getUnitPricingMode(): string
    {
        $mode = (string) $this->scopeConfig->getValue(self::XML_PATH_UNIT_PRICING_MODE);
        $validModes = [
            UnitPricingMeasureMode::MODE_DISABLED,
            UnitPricingMeasureMode::MODE_WHITELIST,
            UnitPricingMeasureMode::MODE_ALL,
        ];

        // Unknown values fall back to the safest mode: leave the column empty
        return in_array($mode, $validModes, true) ? $mode : UnitPricingMeasureMode::MODE_DISABLED;
    }

    /**
     * @return string[]
     */
    private function getUnitPricingWhitelistedParentSkus(): array
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_PATH_UNIT_PRICING_SKU_WHITELIST);

        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            static fn ($value) => $value !== ''
        ));
    }

    /**
     * @param array<string, mixed> $rowData
     */
    private function getRowPriceValue(array $rowData, string $fieldKey): ?float
    {
        if (!array_key_exists($fieldKey, $rowData)) {
            return null;
        }

        return $this->toFloat($rowData[$fieldKey]);
    }

    /**
     * @param array<string, mixed> $rowData
     */
    private function resolveSku(array $rowData): string
    {
        foreach (['basic|sku', 'product|sku'] as $key) {
            if (!empty($rowData[$key])) {
                return (string) $rowData[$key];
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $field
     */
    private function isPriceField(array $field): bool
    {
        $attribute = (string) ($field['attribute'] ?? '');

        if (in_array($attribute, self::PRICE_ATTRIBUTES, true)) {
            return true;
        }

        if (str_starts_with($attribute, 'price|')) {
            $code = explode('|', $attribute)[1] ?? '';

            return in_array($code, ['price', 'final_price', 'regular_price'], true);
        }

        return false;
    }
	private function isPrice(array $field): bool
    {
        $attribute = (string) ($field['attribute'] ?? '');

        if (str_starts_with($attribute, 'price|')) {
            $code = explode('|', $attribute)[1] ?? '';

            return in_array($code, ['price'], true);
        }

        return false;
    }
	private function isFinalPrice(array $field): bool
    {
        $attribute = (string) ($field['attribute'] ?? '');

        if (str_starts_with($attribute, 'price|')) {
            $code = explode('|', $attribute)[1] ?? '';

            return in_array($code, ['final_price'], true);
        }

        return false;
    }

    /**
     * @param array<string, mixed> $field
     */
    private function getFieldKey(array $field): string
    {
        $postfix = (isset($field['parent']) && $field['parent'] === 'yes') ? '|parent' : '';

        return (string) ($field['attribute'] ?? '') . $postfix;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCsvFields(Csv $subject): array
    {
        $property = new ReflectionProperty(Csv::class, 'csvField');
        $property->setAccessible(true);
        $csvFields = $property->getValue($subject);

        return is_array($csvFields) ? $csvFields : [];
    }

    /**
     * @param mixed $value
     */
    private function toFloat($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
	public function getParentSkuByChildSku(string $childSku): ?string
    {
        try {
            $childProduct = $this->productRepository->get($childSku);

            $parentIds = $this->configurable->getParentIdsByChild(
                (int) $childProduct->getId()
            );

            if (!empty($parentIds)) {
                $parentProduct = $this->productRepository->getById($parentIds[0]);
                return $parentProduct->getSku();
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
	public function createLogs($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/productskus.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
	/**
     * @param mixed $productCalculatedPrice
     */
    private function extractCalculatedPriceValue($productCalculatedPrice): ?float
    {
        if (is_array($productCalculatedPrice)) {
            $price = $productCalculatedPrice['price'] ?? null;

            return is_numeric($price) ? (float) $price : null;
        }

        return is_numeric($productCalculatedPrice) ? (float) $productCalculatedPrice : null;
    }
}
