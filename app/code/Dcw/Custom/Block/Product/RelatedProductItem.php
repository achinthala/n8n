<?php
/**
 * Product item block for related products with theme-aware cache key.
 * Merges cache_key_info so block cache varies by theme (mobile/desktop).
 */
declare(strict_types=1);

namespace Dcw\Custom\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;

class RelatedProductItem extends AbstractProduct
{
    /**
     * Merge cache_key_info from data so theme/product are in cache key.
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return array_merge(
            parent::getCacheKeyInfo(),
            (array) $this->getData('cache_key_info')
        );
    }
}
