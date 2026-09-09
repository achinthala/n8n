<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\ProductAlerts\Block\Email;

use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchData;
use Dcw\ProductImage\Helper\Data as ProductImageHelper;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\ScopeInterface;

/**
 * ProductAlert email back in stock grid
 */
class Stock extends \Magento\ProductAlert\Block\Email\Stock
{
    private const COLOR_ATTRIBUTE = 'incstores_pim_color_axis';

    private const EMAIL_IMAGE_ID = 'product_stock_alert_email_product_image';

    /**
     * @var string
     */
    protected $_template = 'Dcw_ProductAlerts::email/stock.phtml';

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AdvanceSearchData
     */
    private $advanceSearchData;

    /**
     * @var ProductImageHelper
     */
    private $productImageHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CatalogImageHelper
     */
    private $catalogImageHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Filter\Input\MaliciousCode $maliciousCode,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        StockRegistryInterface $stockRegistry,
        ScopeConfigInterface $scopeConfig,
        AdvanceSearchData $advanceSearchData,
        ProductImageHelper $productImageHelper,
        ProductRepositoryInterface $productRepository,
        CatalogImageHelper $catalogImageHelper,
        array $data = []
    ) {
        parent::__construct($context, $maliciousCode, $priceCurrency, $imageBuilder, $data);
        $this->stockRegistry = $stockRegistry;
        $this->scopeConfig = $scopeConfig;
        $this->advanceSearchData = $advanceSearchData;
        $this->productImageHelper = $productImageHelper;
        $this->productRepository = $productRepository;
        $this->catalogImageHelper = $catalogImageHelper;
    }

    /**
     * Match Amasty Xnotif stock qty logic (StockRegistry + child check for composite products).
     */
    public function getProductQty(Product $product): ?float
    {
        $websiteId = (int) $this->getStore()->getWebsiteId();
        $child = $this->getFirstAvailableChild($product);

        if ($product->isComposite()) {
            return $child ? $this->getStockRegistryQty($child, $websiteId) : null;
        }

        return $this->getStockRegistryQty($product, $websiteId);
    }

    public function getColorPattern(Product $product): string
    {
        $source = $this->getFirstAvailableChild($product) ?? $product;
        $color = $source->getAttributeText(self::COLOR_ATTRIBUTE);

        if (is_array($color)) {
            return implode(', ', array_filter($color));
        }

        return is_string($color) ? $color : '';
    }

    public function getProductImageHtml(Product $product): string
    {
        $imageUrl = $this->resolveProductImageUrl($product);

        if ($imageUrl === '') {
            $imageUrl = $this->catalogImageHelper->getDefaultPlaceholderUrl('small_image');
        }

        return sprintf(
            '<img src="%s" alt="%s" class="photo image" width="76" height="76" />',
            $this->escapeUrl($imageUrl),
            $this->escapeHtmlAttr((string) $product->getName())
        );
    }

    public function getProductPriceHtmlForEmail(Product $product): string
    {
        $priceData = $this->advanceSearchData->getCalculatedPriceForProduct($product, 'final');

        if ($this->getCalculatedPriceValue($priceData) <= 0) {
            $priceData = $this->advanceSearchData->getCalculatedPrice((int) $product->getId(), 'final');
        }

        $calculatedPrice = $this->getCalculatedPriceValue($priceData);
        if ($calculatedPrice > 0) {
            return $this->formatCalculatedPriceHtml(
                $calculatedPrice,
                (string) ($priceData['calType'] ?? 'each')
            );
        }

        return $this->getMagentoFallbackPriceHtml($product);
    }

    private function formatCalculatedPriceHtml(float $price, string $calType): string
    {
        if ($calType === '' || $calType === 'none') {
            $calType = 'each';
        }

        $formattedPrice = $this->priceCurrency->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getStore()
        );

        return $formattedPrice . '/' . $calType;
    }

    private function getCalculatedPriceValue(array $priceData): float
    {
        if (!isset($priceData['price']) || $priceData['price'] === '') {
            return 0.0;
        }

        return (float) $priceData['price'];
    }

    private function getMagentoFallbackPriceHtml(Product $product): string
    {
        foreach ($this->getImageProductCandidates($product) as $candidate) {
            $price = $this->getMagentoFinalPrice($candidate);
            if ($price > 0) {
                return $this->priceCurrency->format(
                    $price,
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    $this->getStore()
                );
            }
        }

        return '';
    }

    private function getMagentoFinalPrice(Product $product): float
    {
        try {
            $loadedProduct = $this->loadProductForImage((int) $product->getId());
        } catch (\Exception $e) {
            $loadedProduct = $product;
        }

        if ($loadedProduct->getTypeId() === Configurable::TYPE_CODE) {
            $priceInfo = $loadedProduct->getPriceInfo();
            if ($priceInfo) {
                $finalPrice = $priceInfo->getPrice('final_price');
                if ($finalPrice && method_exists($finalPrice, 'getMinimalPrice')) {
                    return (float) $finalPrice->getMinimalPrice()->getValue();
                }
            }

            return (float) $loadedProduct->getFinalPrice(1);
        }

        return (float) $loadedProduct->getFinalPrice();
    }

    private function resolveProductImageUrl(Product $product): string
    {
        foreach ($this->getImageProductCandidates($product) as $candidate) {
            $cantoUrl = $this->getCantoImageUrl($candidate);
            if ($cantoUrl !== '') {
                return $cantoUrl;
            }

            $magentoUrl = $this->getMagentoImageUrl($candidate);
            if ($magentoUrl !== '') {
                return $magentoUrl;
            }
        }

        return '';
    }

    private function getCantoImageUrl(Product $product): string
    {
        $loadedProduct = $this->loadProductForImage((int) $product->getId());
        $cantoBase = $this->productImageHelper->getMainImageUrl($loadedProduct);

        if ($cantoBase === '') {
            return '';
        }

        $dimension = $this->productImageHelper->getSmallImageDimension();

        return rtrim($cantoBase, '/') . '/-B' . $dimension . '-FWEBP';
    }

    private function getMagentoImageUrl(Product $product): string
    {
        $loadedProduct = $this->loadProductForImage((int) $product->getId());

        foreach (['small_image', 'image', 'thumbnail'] as $imageType) {
            $value = (string) $loadedProduct->getData($imageType);
            if ($value === '' || $value === 'no_selection') {
                continue;
            }

            try {
                $url = $this->catalogImageHelper
                    ->init($loadedProduct, self::EMAIL_IMAGE_ID, ['type' => $imageType])
                    ->getUrl();

                if ($url && strpos($url, 'placeholder') === false) {
                    return $url;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return '';
    }

    /**
     * @return Product[]
     */
    private function getImageProductCandidates(Product $product): array
    {
        $candidates = [$product];
        $minPriceChildId = $this->getMinPriceChildProductId($product);

        if ($minPriceChildId) {
            try {
                $candidates[] = $this->loadProductForImage($minPriceChildId);
            } catch (\Exception $e) {
                // Continue with other candidates.
            }
        }

        if ($product->isComposite()) {
            $availableChild = $this->getFirstAvailableChild($product);
            if ($availableChild) {
                $candidates[] = $availableChild;
            }

            foreach ($this->getUsedProducts($product) as $child) {
                if ($child instanceof Product) {
                    $candidates[] = $child;
                }
            }
        }

        $unique = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $id = (int) $candidate->getId();
            if ($id > 0 && !isset($seen[$id])) {
                $seen[$id] = true;
                $unique[] = $candidate;
            }
        }

        return $unique;
    }

    private function getMinPriceChildProductId(Product $product): ?int
    {
        try {
            $product = $this->loadProductForImage((int) $product->getId());
        } catch (\Exception $e) {
            // Use the product already loaded in the email collection.
        }

        $width = $product->getData('incstores_pim_exact_width_inches');
        $length = $product->getData('incstores_pim_exact_length_inches');
        $coverageArea = $product->getIncstoresPimCoverage();
        $minPriceChildProductId = null;

        if (!is_numeric($width) && !is_numeric($length) || !is_numeric($coverageArea)) {
            if ($width !== null && strpos((string) $width, '_') !== false) {
                $configExactWidthArr = explode('_', (string) $width);
                if (!empty($configExactWidthArr[0]) && (int) $configExactWidthArr[0] > 0) {
                    $minPriceChildProductId = (int) $configExactWidthArr[0];
                }
            }

            if ($length !== null && strpos((string) $length, '_') !== false) {
                $configExactLengthArr = explode('_', (string) $length);
                if (!empty($configExactLengthArr[0]) && (int) $configExactLengthArr[0] > 0) {
                    $minPriceChildProductId = (int) $configExactLengthArr[0];
                }
            }
        }

        return $minPriceChildProductId;
    }

    private function loadProductForImage(int $productId): Product
    {
        return $this->productRepository->getById($productId, false, (int) $this->getStore()->getId());
    }

    private function getFirstAvailableChild(Product $product): ?Product
    {
        if (!$product->isComposite()) {
            return null;
        }

        $websiteId = (int) $this->getStore()->getWebsiteId();
        $minQty = $this->getMinQty();

        foreach ($this->getUsedProducts($product) as $child) {
            if (!$child instanceof Product) {
                continue;
            }

            $qty = $this->getStockRegistryQty($child, $websiteId);
            if ($qty !== null && $child->isSalable() && $qty >= $minQty) {
                return $child;
            }
        }

        return null;
    }

    private function getStockRegistryQty(ProductInterface $product, int $websiteId): ?float
    {
        $stockStatus = $this->stockRegistry->getStockStatusBySku($product->getSku(), $websiteId);

        return $stockStatus ? (float) $stockStatus->getQty() : null;
    }

    private function getMinQty(): float
    {
        $minQty = $this->scopeConfig->getValue(
            'amxnotif/general/min_qty',
            ScopeInterface::SCOPE_STORE,
            $this->getStore()->getId()
        );

        return $minQty !== null ? (float) $minQty : 1.0;
    }

    /**
     * @return ProductInterface[]
     */
    private function getUsedProducts(Product $product): array
    {
        switch ($product->getTypeId()) {
            case Configurable::TYPE_CODE:
                return $product->getTypeInstance()->getUsedProducts($product);
            case Grouped::TYPE_CODE:
                return $product->getTypeInstance()->getAssociatedProducts($product);
            case BundleType::TYPE_CODE:
                return $product->getTypeInstance()->getSelectionsCollection(
                    $product->getTypeInstance()->getOptionsIds($product),
                    $product
                )->getItems();
            default:
                return [$product];
        }
    }
}
