<?php

declare(strict_types=1);

namespace Dcw\Spotlight\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLE_VARIANTS = 'dcw_spotlight/schema_variants/enable_variants';
    const XML_PATH_ENABLE_CACHE = 'dcw_spotlight/schema_variants/enable_cache';
    const XML_PATH_CACHE_LIFETIME = 'dcw_spotlight/schema_variants/cache_lifetime';
    const XML_PATH_ONLY_ENABLED = 'dcw_spotlight/schema_variants/include_only_enabled';
    const XML_PATH_ONLY_IN_STOCK = 'dcw_spotlight/schema_variants/include_only_in_stock';

    /**
     * Check if variants should be included in JSON-LD schema
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isVariantsEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_VARIANTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if cache is enabled for JSON-LD data
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCacheEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_CACHE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get cache lifetime in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getCacheLifetime(?int $storeId = null): int
    {
        $value = (int)$this->scopeConfig->getValue(
            self::XML_PATH_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        // Default to 24 hours if not set
        return $value >= 0 ? $value : 86400;
    }

    /**
     * Check if only enabled variants should be included
     *
     * @param int|null $storeId
     * @return bool
     */
    public function includeOnlyEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ONLY_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if only in-stock variants should be included
     *
     * @param int|null $storeId
     * @return bool
     */
    public function includeOnlyInStock(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ONLY_IN_STOCK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

}

