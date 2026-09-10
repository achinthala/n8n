<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Module configuration reader.
 */
class Config
{
    private const XML_PATH_ENABLED = 'dcw_product_data_validation/general/enabled';
    private const XML_PATH_ATTRIBUTES = 'dcw_product_data_validation/general/attributes';
    private const XML_PATH_STORE_IDS = 'dcw_product_data_validation/general/store_ids';
    private const XML_PATH_BATCH_SIZE = 'dcw_product_data_validation/general/batch_size';
    private const XML_PATH_SAMPLE_ATTRIBUTE_SET =
        'dcw_product_data_validation/general/exclude_sample_attribute_set_id';

    private const DEFAULT_BATCH_SIZE = 500;
    private const DEFAULT_SAMPLE_ATTRIBUTE_SET_ID = 455;

    /**
     * Attribute codes treated as HTML content for emptiness checks.
     */
    private const HTML_ATTRIBUTES = [
        'description',
        'short_description',
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * @return string[]
     */
    public function getRequiredAttributes(): array
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTES);
        if ($value === '') {
            return [];
        }

        $attributes = array_map('trim', explode(',', $value));
        $attributes = array_filter($attributes, static fn(string $code): bool => $code !== '');

        return array_values(array_unique($attributes));
    }

    /**
     * Store IDs to validate. Defaults to Default Store View when unset.
     *
     * @return int[]
     */
    public function getStoreIds(): array
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_STORE_IDS);
        if ($value === '') {
            return [(int) $this->storeManager->getDefaultStoreView()?->getId() ?: Store::DISTRO_STORE_ID];
        }

        $ids = array_map('intval', array_filter(array_map('trim', explode(',', $value))));
        if ($ids === []) {
            return [(int) $this->storeManager->getDefaultStoreView()?->getId() ?: Store::DISTRO_STORE_ID];
        }

        return array_values(array_unique($ids));
    }

    public function getBatchSize(): int
    {
        $size = (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE);

        return $size > 0 ? $size : self::DEFAULT_BATCH_SIZE;
    }

    public function getSampleAttributeSetId(): int
    {
        $setId = $this->scopeConfig->getValue(self::XML_PATH_SAMPLE_ATTRIBUTE_SET);
        if ($setId === null || $setId === '') {
            return self::DEFAULT_SAMPLE_ATTRIBUTE_SET_ID;
        }

        return (int) $setId;
    }

    /**
     * @return string[]
     */
    public function getHtmlAttributes(): array
    {
        return self::HTML_ATTRIBUTES;
    }
}
