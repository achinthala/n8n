<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\ViewModel\Product;

use Dcw\ShipRegionAvailability\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class ShipRegionAvailability implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly UrlInterface $urlBuilder,
        private readonly Registry $registry,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getProduct(): ?ProductInterface
    {
        $product = $this->registry->registry('product');
        return $product instanceof ProductInterface ? $product : null;
    }

    /**
     * Store-scoped module enable flag (Stores → Configuration).
     */
    public function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->config->isEnabled($storeId);
    }

    /**
     * Module enabled for store AND parent product flag set.
     */
    public function isEnabledForProduct(?ProductInterface $product = null): bool
    {
        $product = $product ?? $this->getProduct();
        if ($product === null || $product->getTypeId() !== 'configurable') {
            return false;
        }

        if (!$this->isModuleEnabled($this->resolveStoreId($product))) {
            return false;
        }

        if (!$product->hasData(Config::ATTR_SHIP_REGION_AVAILABILITY)) {
            $product = clone $product;
            if ($product instanceof Product) {
                $product->load($product->getId());
            }
        }

        if ((int) $product->getData(Config::ATTR_SHIP_REGION_AVAILABILITY) !== 1) {
            return false;
        }

        if ($product instanceof Product && !$product->isSaleable()) {
            return false;
        }

        return true;
    }

    public function getZipLookupUrl(): string
    {
        return $this->urlBuilder->getUrl('dcw_shipregion/zip/lookup');
    }

    private function resolveStoreId(?ProductInterface $product): ?int
    {
        if ($product !== null && $product->getStoreId()) {
            return (int) $product->getStoreId();
        }

        try {
            return (int) $this->storeManager->getStore()->getId();
        } catch (\Exception) {
            return null;
        }
    }
}
