<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Google Rich Snippets for Magento 2
 */

namespace Dcw\Spotlight\Model\JsonLd;

use Amasty\SeoRichData\Block\Product as ProductBlock;
use Amasty\SeoRichData\Helper\Config as ConfigHelper;
use Amasty\SeoRichData\Model\ConfigProvider;
use Amasty\SeoRichData\Model\JsonLd\Processor\ProductProcessorInterface;
use Amasty\SeoRichData\Model\Review\GetAggregateRating;
use Amasty\SeoRichData\Model\Review\GetReviews;
use Amasty\SeoRichData\Model\Source\Product\Description as DescriptionSource;
use Amasty\SeoRichData\Model\Source\Product\OfferItemCondition as OfferItemConditionSource;
use DateTimeInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Store\Model\StoreManagerInterface;
use Dcw\Spotlight\ViewModel\SchemaData as SpotlightSchemaData;
use Dcw\Spotlight\Helper\Config as SpotlightConfig;
use Dcw\Spotlight\Model\Cache\JsonLdCache;
use Magento\Framework\App\RequestInterface;
use Dcw\Spotlight\Model\ResourceModel\ProductRatings\CollectionFactory as RatingsCollectionFactory;
use Dcw\Spotlight\Model\ResourceModel\TopReviews\CollectionFactory as ReviewsCollectionFactory;
use Dcw\ProductImage\Helper\Data as ProductImageHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;

class ProductInfo extends \Amasty\SeoRichData\Model\JsonLd\ProductInfo
{
    /**
     * @var PageConfig
     */
    private $pageConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var OfferItemConditionSource
     */
    private $offerItemConditionSource;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var GetReviews
     */
    private $getReviews;

    /**
     * @var GetAggregateRating
     */
    private $getAggregateRating;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var ProductProcessorInterface[]
     */
    private $processors;

    /**
     * @var SpotlightSchemaData
     */
    protected $spotlightSchemaData;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var SpotlightConfig
     */
    protected $spotlightConfig;

    /**
     * @var JsonLdCache
     */
    protected $jsonLdCache;

    /**
     * @var RatingsCollectionFactory
     */
    protected $ratingsCollectionFactory;

    /**
     * @var ReviewsCollectionFactory
     */
    protected $reviewsCollectionFactory;

    /**
     * @var ProductImageHelper
     */
    private $productImageHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ConfigurableType
     */
    private $configurableType;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var string|null
     */
    private $pageTypeForExtract;

    public function __construct(
        PageConfig $pageConfig,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry,
        ConfigHelper $configHelper,
        ImageHelper $imageHelper,
        DateTime $dateTime,
        ConfigProvider $configProvider,
        OfferItemConditionSource $offerItemConditionSource,
        ProductResource $productResource,
        GetReviews $getReviews,
        GetAggregateRating $getAggregateRating,
        FilterManager $filterManager,
        SpotlightSchemaData $spotlightSchemaData,
        RequestInterface $request,
        SpotlightConfig $spotlightConfig,
        JsonLdCache $jsonLdCache,
        RatingsCollectionFactory $ratingsCollectionFactory,
        ReviewsCollectionFactory $reviewsCollectionFactory,
        ProductImageHelper $productImageHelper,
        ProductRepositoryInterface $productRepository,
        ConfigurableType $configurableType,
        CategoryCollectionFactory $categoryCollectionFactory,
        array $processors = []
    ) {
        // Set all properties
        $this->pageConfig = $pageConfig;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
        $this->configHelper = $configHelper;
        $this->imageHelper = $imageHelper;
        $this->dateTime = $dateTime;
        $this->configProvider = $configProvider;
        $this->offerItemConditionSource = $offerItemConditionSource;
        $this->productResource = $productResource;
        $this->getReviews = $getReviews;
        $this->getAggregateRating = $getAggregateRating;
        $this->filterManager = $filterManager;
        $this->spotlightSchemaData = $spotlightSchemaData;
        $this->request = $request;
        $this->spotlightConfig = $spotlightConfig;
        $this->jsonLdCache = $jsonLdCache;
        $this->ratingsCollectionFactory = $ratingsCollectionFactory;
        $this->reviewsCollectionFactory = $reviewsCollectionFactory;
        $this->productImageHelper = $productImageHelper;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->processors = $processors;
    }

    /**
     * Set page type for extract (e.g. 'spotlight') - used when called from ViewModel
     *
     * @param string|null $pageType
     * @return $this
     */
    public function setPageType(?string $pageType): self
    {
        $this->pageTypeForExtract = $pageType;
        return $this;
    }

    public function extract(ProductModel $product = null): array
    {
        $pageType = $this->pageTypeForExtract ?? '';
        $this->pageTypeForExtract = null;
        $storeId = (int)$this->storeManager->getStore()->getId();
        
        // Try to get from cache first (TRIGGER 3: Product Page Visit)
        // Cache includes full JSON-LD data (product info + Bazaarvoice reviews + ratings)
        if ($this->spotlightConfig->isCacheEnabled($storeId)) {
            $cachedData = $this->jsonLdCache->get((int)$product->getId(), $storeId);
            if ($cachedData !== null) {
                return $cachedData; // Cache HIT - Return cached data (includes reviews) ⚡
            }
        }
        
        // Cache MISS - Generate fresh data (includes Bazaarvoice reviews from database)
        $resultArray = $this->generateJsonLd($product, $pageType);
        
        // Clean array to ensure JSON validity
        $resultArray = $this->cleanArrayForJson($resultArray);
        
        // Save to cache if enabled (includes aggregateRating and review data from Bazaarvoice tables)
        if ($this->spotlightConfig->isCacheEnabled($storeId)) {
            $this->jsonLdCache->save((int)$product->getId(), $storeId, $resultArray);
        }
        
        return $resultArray;
    }

    /**
     * Generate JSON-LD data for product
     *
     * @param ProductModel $product
     * @param string|null $pageType
     * @return array
     */
    private function generateJsonLd(ProductModel $product, ?string $pageType = null): array
    {
        $productDescription = $this->replaceDescription($product);
        $image = $this->getProductImageUrl($product);
        
        $productForUrl = $this->getProductForImage($product);
        $productUrl = $productForUrl->getProductUrl();

        // For configurable products with variants enabled, use ProductGroup (not on spotlight)
        $isConfigurableWithVariants = $this->spotlightConfig->isVariantsEnabled() 
            && $product->getTypeId() === ConfigurableType::TYPE_CODE;
        if ($pageType === 'spotlight') {
            $isConfigurableWithVariants = false;
        }
        // Exclude hasVariant when product has reviews - Google requires itemReviewed to reference Product (not ProductGroup)
        if ($isConfigurableWithVariants && $this->configProvider->isShowRating() && $pageType !== 'spotlight') {
            $aggregateRating = $this->getBazaarvoiceAggregateRating($product);
            if (!empty($aggregateRating) && isset($aggregateRating['reviewCount']) && $aggregateRating['reviewCount'] > 0) {
                $isConfigurableWithVariants = false;
            }
        }
        
        // Clean description
        $cleanDescription = $this->sanitizeString($this->filterManager->stripTags(html_entity_decode($productDescription)));
        if (empty($cleanDescription)) {
            $cleanDescription = $this->sanitizeString($product->getName()); // Fallback to product name
        }
        
        $resultArray = [
            '@context' => 'https://schema.org',
            '@type' => $isConfigurableWithVariants ? 'ProductGroup' : 'Product',
            '@id' => $productUrl . '#group',
            'name' => $this->sanitizeString($product->getName()),
            'sku' => $this->sanitizeString($product->getSku()),
            'description' => $cleanDescription,
            'image' => $image,
            'url' => $productUrl
        ];

        // Add hasVariant for configurable products (if enabled in config)
        if ($isConfigurableWithVariants) {
            $variants = $this->prepareVariants($product);
            if (!empty($variants)) {
                $resultArray['hasVariant'] = $variants;
            }
        } else {
            $resultArray['offers'] = $this->generateVariantOffer(
                $product,
                $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(),
                $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                $this->getAvailabilityCondition($product),
                $productUrl,
                $productUrl . '#offer-' . $this->sanitizeString($product->getSku())
            );
        }

        // Always add brand (required for ProductGroup)
        $brandInfo = $this->getBrandInfo($product);
        if (!empty($brandInfo) && is_array($brandInfo)) {
            $resultArray['brand'] = $brandInfo;
        }

        $manufacturerInfo = $this->getManufacturerInfo($product);
        if (!empty($manufacturerInfo) && is_array($manufacturerInfo)) {
            $resultArray['manufacturer'] = $manufacturerInfo;
        }

        $this->updateCustomProperties($resultArray, $product);

        foreach ($this->processors as $processor) {
            $resultArray = $processor->process($resultArray, $product);
        }

        // Add Bazaarvoice reviews AFTER processors - overwrite any Amasty-added reviews to prevent duplicates
        if ($this->configProvider->isShowRating() && $pageType !== 'spotlight') {
            $aggregateRating = $this->getBazaarvoiceAggregateRating($product);
            if (!empty($aggregateRating) && isset($aggregateRating['reviewCount']) && $aggregateRating['reviewCount'] > 0) {
                $resultArray['aggregateRating'] = $aggregateRating;
            }
            $reviews = $this->getBazaarvoiceReviews($product);
            if (!empty($reviews)) {
                $resultArray['review'] = array_slice($reviews, 0, 5);
            }
        }

        return $resultArray;
    }

    protected function prepareOffers(ProductModel $product): array
    {
        $offers = [];
        $priceCurrency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $orgName = $this->storeManager->getStore()->getFrontendName();
        $productType = $product->getTypeId();

        switch ($productType) {
            case ConfigurableType::TYPE_CODE:
            case GroupedType::TYPE_CODE:
                if ($this->configHelper->showAggregate($productType)) {
                    $offers[] = $this->generateAggregateOffers(
                        $this->getSimpleProducts($product),
                        $priceCurrency
                    );
                } elseif ($this->configHelper->showAsList($productType)) {
                    foreach ($this->getSimpleProducts($product) as $child) {
                        $offers[] = $this->generateOffers($child, $priceCurrency, $orgName, $product);
                    }
                } else {
                    $offers[] = $this->generateOffers($product, $priceCurrency, $orgName);
                }
                break;
            default:
                $offers[] = $this->generateOffers($product, $priceCurrency, $orgName);
        }

        return $offers;
    }

    private function replaceDescription(ProductModel $product): string
    {
        return preg_replace(
            '#(\<style\>)(.*?)(\<\/style\>)#ims',
            '',
            $this->getProductDescription($product)
        );
    }

    private function getSimpleProducts(ProductModel $product): array
    {
        $list = [];
        $typeInstance = $product->getTypeInstance();

        switch ($product->getTypeId()) {
            case ConfigurableType::TYPE_CODE:
                $list = $typeInstance->getUsedProducts($product);
                break;
            case GroupedType::TYPE_CODE:
                $list = $typeInstance->getAssociatedProducts($product);
                break;
        }

        return $list;
    }

    private function generateAggregateOffers(array $listOfSimples, string $priceCurrency): array
    {
        $minPrice = INF;
        $maxPrice = 0;
        $offerCount = 0;

        foreach ($listOfSimples as $child) {
            $childPrice = $child->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            $minPrice = min($minPrice, $childPrice);
            $maxPrice = max($maxPrice, $childPrice);
            $offerCount++;
        }

        return [
            '@type' => 'AggregateOffer',
            'lowPrice' => $minPrice ? round($minPrice, 2) : 0.0,
            'highPrice' => $maxPrice ? round($maxPrice, 2) : 0.0,
            'offerCount' => $offerCount,
            'priceCurrency' => $priceCurrency
        ];
    }

    protected function unsetUnnecessaryData(array $offers): array
    {
        if (!$this->configProvider->isShowAvailability()) {
            foreach ($offers as $key => $offer) {
                if (isset($offer['availability'])) {
                    unset($offers[$key]['availability']);
                }
            }
        }

        if (!$this->configHelper->showCondition()) {
            foreach ($offers as $key => $offer) {
                if (isset($offer['itemCondition'])) {
                    unset($offers[$key]['itemCondition']);
                }
            }
        }

        return $offers;
    }

    protected function generateOffers(
        ProductModel $product,
        string $priceCurrency,
        string $orgName,
        ?ProductModel $parentProduct = null
    ): array {
        if ($parentProduct
            && !in_array($this->getProductVisibility($product), ProductBlock::VISIBILITY)
        ) {
            $productUrl = $parentProduct->getProductUrl();
        } else {
            $productUrl = $product->getProductUrl();
        }

        $productId = $product->getId();
        $colorSku = $this->request->getParam('option');
        $minimumPriceProductId = $this->spotlightSchemaData->getSelectedProduct($productId);

        if ($colorSku) {
            $colorSkuLoad = $this->spotlightSchemaData->loadProductBySku($colorSku);
            
            if ($colorSkuLoad) {
                $product = $colorSkuLoad;
                $productId = $product->getId();
                $minimumPriceProductId = $productId;
            }
        }

        $getUnitPriceSpecification = $this->spotlightSchemaData->getUnitPriceSpecification($minimumPriceProductId);
        $referenceQuantityValue = $this->spotlightSchemaData->getReferenceQuantityValue($minimumPriceProductId);

        $itemConditionValue = $product->hasData(OfferItemConditionSource::ATTRIBUTE_CODE)
            ? (int)$product->getData(OfferItemConditionSource::ATTRIBUTE_CODE)
            : OfferItemConditionSource::NEW_CONDITION;
        $offers = [
            '@type' => 'Offer',
            'priceSpecification' => [
                '@type' => 'UnitPriceSpecification',
                'price' => $getUnitPriceSpecification, //This is the full price considering price x min order qty x min roll cut (for roll type)
                'priceCurrency' => $priceCurrency,
                'referenceQuantity' => [
                    '@type' => 'QuantitativeValue',
                    'value' => $referenceQuantityValue, //this will be (exact_length x exact_width)/144 x items per box x min order qty X min roll cut (for roll type)
                    'unitCode' => 'FTK',
                    'valueReference' => [
                        '@type' => 'QuantitativeValue',
                        'value' => '1',
                        'unitCode' => 'FTK'
                    ]
                ]
            ],
            'availability' => $this->getAvailabilityCondition($product),
            'itemCondition' => $this->offerItemConditionSource->getConditionValue($itemConditionValue),
            'seller' => [
                '@type' => 'Organization',
                'name' => $orgName
            ],
            'url' => $productUrl
        ];

        $this->updateCustomProperties($offers, $product);

        if ($this->configProvider->isReplacePriceValidUntil()
            && $product->getSpecialPrice()
            && $this->dateTime->timestamp() < $this->dateTime->timestamp($product->getSpecialToDate())
        ) {
            $offers['priceValidUntil'] = $this->dateTime->date(DateTimeInterface::ATOM, $product->getSpecialToDate());
        } elseif ($this->configProvider->getDefaultPriceValidUntil()) {
            $offers['priceValidUntil'] = $this->dateTime->date(
                DateTimeInterface::ATOM,
                $this->configProvider->getDefaultPriceValidUntil()
            );
        }

        return $offers;
    }

    private function getProductVisibility(ProductModel $product): int
    {
        $visibility = $product->getVisibility();
        if ($visibility === null) {
            $visibility = $this->productResource->getAttributeRawValue(
                $product->getId(),
                ProductInterface::VISIBILITY,
                $this->storeManager->getStore()->getId()
            );
        }

        return (int)$visibility;
    }

    private function getBrandInfo(ProductModel $product): ?array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $brandName = 'Flooring Inc';
        $categoryIds = $product->getCategoryIds();
        if (!empty($categoryIds)) {
            $category = $this->getFirstEnabledBrandCategory($categoryIds, $storeId);
            if ($category) {
                $brandName = $category->getName();
            }
        }

        return [
            '@type' => 'Brand',
            'name' => $this->sanitizeString((string)$brandName),
        ];
    }

    /**
     * First assigned category flagged as brand (is_brand), same collection + loop pattern as Dcw\BrandLogo\Block\Index::getBrandLogo.
     *
     * @param array<int|string> $categoryIds
     */
    private function getFirstEnabledBrandCategory(array $categoryIds, int $storeId): ?CategoryInterface
    {
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect(['entity_id', 'name', 'is_brand'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->setStoreId($storeId);

        foreach ($categoryCollection as $category) {
            if ((bool)$category->getIsBrand()) {
                return $category;
            }
        }

        return null;
    }

    private function getManufacturerInfo(ProductModel $product): ?array
    {
        $info = null;
        $manufacturer = $this->configHelper->getManufacturerAttribute();

        if ($manufacturer && $attributeValue = $product->getAttributeText($manufacturer)) {
            $info = [
                '@type' => 'Organization',
                'name' => $attributeValue
            ];
        }

        return $info;
    }

    public function getAvailabilityCondition(ProductModel $product): string
    {
        $availabilityCondition = $this->stockRegistry->getProductStockStatus($product->getId())
            ? ProductBlock::IN_STOCK
            : ProductBlock::OUT_OF_STOCK;

        return $availabilityCondition;
    }

    private function updateCustomProperties(array &$result, ProductModel $product): void
    {
        foreach ($this->configHelper->getCustomAttributes() as $pair) {
            $snippetProperty = isset($pair[0]) ? trim($pair[0]) : null;
            $attributeCode = isset($pair[1]) ? trim($pair[1]) : $snippetProperty;

            if ($snippetProperty && $attributeCode) {
                if ($product->getData($attributeCode)) {
                    $result[$snippetProperty] = $product->getAttributeText($attributeCode)
                        ? $product->getAttributeText($attributeCode)
                        : $product->getData($attributeCode);
                }
            }
        }
    }

    private function getProductDescription(ProductModel $product): string
    {
        $description = '';
        switch ($this->configProvider->getProductDescriptionMode((int)$this->storeManager->getStore()->getId())) {
            case DescriptionSource::SHORT_DESCRIPTION:
                $description = $this->getMetaData($product, 'short_description') ?: $product->getShortDescription();
                break;
            case DescriptionSource::FULL_DESCRIPTION:
                $description = $this->getMetaData($product, 'description') ?: $product->getDescription();
                break;
            case DescriptionSource::META_DESCRIPTION:
                $description = $this->getMetaData($product, 'meta_description')
                    ?: $this->pageConfig->getDescription();
                break;
        }

        return (string)$description;
    }

    /**
     * Value of this method resolved in Amasty_Meta \Amasty\Meta\Plugin\SeoRichData\Block\Product
     */
    public function getMetaData(ProductModel $product, string $key): string
    {
        return '';
    }

    private function getPrice(ProductModel $product): float
    {
        $msrp = (float)$product->getMsrp();
        $price = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue() ?? 0;

        return round(max($msrp, $price), 2);
    }

    /**
     * Prepare variants for configurable products - loads ALL enabled children
     *
     * @param ProductModel $product
     * @return array
     */
    private function prepareVariants(ProductModel $product): array
    {
        $variants = [];
        $simpleProducts = $this->getSimpleProducts($product);
        
        // Get configuration values
        $includeOnlyEnabled = $this->spotlightConfig->includeOnlyEnabled();
        $includeOnlyInStock = $this->spotlightConfig->includeOnlyInStock();
        
        $priceCurrency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        
        foreach ($simpleProducts as $childProduct) {
            // Skip disabled products if configured
            if ($includeOnlyEnabled && $childProduct->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                continue;
            }
            
            // Skip out of stock products if configured
            if ($includeOnlyInStock && !$this->stockRegistry->getProductStockStatus($childProduct->getId())) {
                continue;
            }
            
            $variant = $this->generateVariant($childProduct, $priceCurrency, $product);
            if ($variant) {
                $variants[] = $variant;
            }
        }
        
        return $variants;
    }

    /**
     * Generate individual variant data
     *
     * @param ProductModel $childProduct
     * @param string $priceCurrency
     * @param ProductModel $parentProduct
     * @return array|null
     */
    private function generateVariant(
        ProductModel $childProduct,
        string $priceCurrency,
        ProductModel $parentProduct
    ): ?array {
        try {
            $childPrice = $childProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            $availability = $this->getAvailabilityCondition($childProduct);
            
            // Get variant name - combine parent name with variant attributes
            $variantName = $this->getVariantName($childProduct, $parentProduct);
            
            $parentUrl = $parentProduct->getProductUrl();
            $variantId = $parentUrl . '#variant-' . $this->sanitizeString($childProduct->getSku());
            $offerId = $parentUrl . '#offer-' . $this->sanitizeString($childProduct->getSku());
            
            // Get image for variant (checks custom_image_link first, then falls back)
            $variantImage = $this->getVariantImage($childProduct);
            
            $variant = [
                '@type' => 'Product',
                '@id' => $variantId,
                'name' => $this->sanitizeString($variantName),
                'sku' => $this->sanitizeString($childProduct->getSku()),
                'url' => $parentUrl,
                'image' => $variantImage,
                'itemCondition' => $this->getItemCondition($childProduct)
            ];
            
            // Add offers with detailed price specification
            $variant['offers'] = $this->generateVariantOffer(
                $childProduct,
                $childPrice,
                $priceCurrency,
                $availability,
                $parentUrl,
                $offerId
            );
            
            // Don't add rating and reviews to variants - they appear at ProductGroup level
            // Reviews are aggregated at parent level for better SEO and cleaner schema
            
            return $variant;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate offer for variant with detailed price specification
     *
     * @param ProductModel $childProduct
     * @param float $price
     * @param string $priceCurrency
     * @param string $availability
     * @param string $url
     * @param string $offerId
     * @return array
     */
    private function generateVariantOffer(
        ProductModel $childProduct,
        float $price,
        string $priceCurrency,
        string $availability,
        string $url,
        string $offerId
    ): array {
        $calculatedPrice = $this->spotlightSchemaData->getCalculatedPriceForProduct($childProduct, 'final');
        if (is_array($calculatedPrice) && isset($calculatedPrice['price'])) {
            $calculatedPrice = (float) $calculatedPrice['price'];
        } else {
            $calculatedPrice = $price;
        }
        $offer = [
            '@type' => 'Offer',
            '@id' => $offerId,
            'price' => round($calculatedPrice, 2),
            'priceCurrency' => $priceCurrency,
            'availability' => $availability,
            'url' => $url
        ];
        
        // Add price valid until
        if ($this->configProvider->isReplacePriceValidUntil()
            && $childProduct->getSpecialPrice()
            && $this->dateTime->timestamp() < $this->dateTime->timestamp($childProduct->getSpecialToDate())
        ) {
            $offer['priceValidUntil'] = $this->dateTime->date(\DateTimeInterface::ATOM, $childProduct->getSpecialToDate());
        } elseif ($this->configProvider->getDefaultPriceValidUntil()) {
            $offer['priceValidUntil'] = $this->dateTime->date(
                \DateTimeInterface::ATOM,
                $this->configProvider->getDefaultPriceValidUntil()
            );
        } else {
            // Default to end of current year
            $offer['priceValidUntil'] = date('Y') . '-12-31';
        }

        // Use in-memory child product — avoids 2× ProductRepository::getById per variant
        $getUnitPriceSpecification = $this->spotlightSchemaData->getUnitPriceSpecificationForProduct($childProduct);
        $referenceQuantityValue = $this->spotlightSchemaData->getReferenceQuantityValueForProduct($childProduct);
        
        // Add detailed price specification
        $offer['priceSpecification'] = [
            '@type' => 'UnitPriceSpecification',
            'price' => $getUnitPriceSpecification,
            'priceCurrency' => $priceCurrency,
            'unitText' => 'sq ft',
            'referenceQuantity' => [
                '@type' => 'QuantitativeValue',
                'value' => $referenceQuantityValue,
                'unitCode' => 'FTK'
            ]
        ];
        
        return $offer;
    }

    /**
     * Get item condition for product
     *
     * @param ProductModel $product
     * @return string
     */
    private function getItemCondition(ProductModel $product): string
    {
        $itemConditionValue = $product->hasData(OfferItemConditionSource::ATTRIBUTE_CODE)
            ? (int)$product->getData(OfferItemConditionSource::ATTRIBUTE_CODE)
            : OfferItemConditionSource::NEW_CONDITION;
        
        return $this->offerItemConditionSource->getConditionValue($itemConditionValue);
    }

    /**
     * Get variant name by combining parent name with variant attributes
     *
     * @param ProductModel $childProduct
     * @param ProductModel $parentProduct
     * @return string
     */
    private function getVariantName(ProductModel $childProduct, ProductModel $parentProduct): string
    {
        $parentName = $parentProduct->getName();
        $variantSuffix = '';
        
        // Try to get configurable attributes
        try {
            $attributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);
            $attributeLabels = [];
            
            foreach ($attributes as $attribute) {
                $attributeCode = $attribute->getProductAttribute()->getAttributeCode();
                $value = $childProduct->getAttributeText($attributeCode);
                
                if ($value) {
                    $attributeLabels[] = $value;
                }
            }
            
            if (!empty($attributeLabels)) {
                $variantSuffix = ' - ' . implode(', ', $attributeLabels);
            }
        } catch (\Exception $e) {
            // Fallback: use SKU if attributes can't be determined
            $variantSuffix = ' - ' . $childProduct->getSku();
        }
        
        return $parentName . $variantSuffix;
    }

    /**
     * Get image for variant
     * First checks custom_image_link in media_gallery, then falls back to default image or placeholder
     *
     * @param ProductModel $product
     * @return string
     */
    private function getVariantImage(ProductModel $product): string
    {
        // Try to get custom image link from media gallery
        $mediaGallery = $product->getData('media_gallery');
        
        if ($mediaGallery && isset($mediaGallery['images']) && is_array($mediaGallery['images'])) {
            foreach ($mediaGallery['images'] as $image) {
                if (isset($image['custom_image_link']) && !empty($image['custom_image_link'])) {
                    return $image['custom_image_link'];
                }
            }
        }
        
        // Try to get product image
        $productImage = $product->getImage();
        if ($productImage && $productImage !== 'no_selection') {
            try {
                return $this->imageHelper->init(
                    $product,
                    'product_page_image_medium_no_frame',
                    ['type' => 'image']
                )->getUrl();
            } catch (\Exception $e) {
                // Fall through to placeholder
            }
        }
        
        // Return placeholder image
        return $this->getPlaceholderImageUrl();
    }

    /**
     * Get product image URL - same logic as spotlight.phtml (Canto image or placeholder)
     *
     * @param ProductModel $product
     * @return string
     */
    private function getProductImageUrl(ProductModel $product): string
    {
        $productForImage = $this->getProductForImage($product);
        $cantoImage = $this->productImageHelper->getMainImageUrl($productForImage);

        // For configurable (parent) products: reload to ensure media gallery is loaded, or try first child's Canto
        if ($cantoImage === '' && $productForImage->getTypeId() === ConfigurableType::TYPE_CODE) {
            try {
                $storeId = $productForImage->getStoreId() ?: (int) $this->storeManager->getStore()->getId();
                $reloadedProduct = $this->productRepository->getById(
                    (int) $productForImage->getId(),
                    false,
                    $storeId
                );
                $cantoImage = $this->productImageHelper->getMainImageUrl($reloadedProduct);
            } catch (\Exception $e) {
                // Ignore
            }
        }
        if ($cantoImage === '' && $productForImage->getTypeId() === ConfigurableType::TYPE_CODE) {
            $childIds = $this->configurableType->getChildrenIds($productForImage->getId());
            $firstChildIds = $childIds[0] ?? [];
            if (!empty($firstChildIds)) {
                try {
                    $firstChild = $this->productRepository->getById((int) reset($firstChildIds));
                    $cantoImage = $this->productImageHelper->getMainImageUrl($firstChild);
                } catch (\Exception $e) {
                    // Ignore, use fallbacks below
                }
            }
        }

        if ($cantoImage !== '') {
            $imagesDimensionMedium = $this->productImageHelper->getSmallImageDimension();
            return $cantoImage . '/-B' . $imagesDimensionMedium;
        }

        // Fallback: try standard Magento product image when no Canto/custom_image_link
        $productImage = $productForImage->getImage();
        if ($productImage && $productImage !== 'no_selection') {
            try {
                $magentoImageUrl = $this->imageHelper->init(
                    $productForImage,
                    'product_page_image_medium_no_frame',
                    ['type' => 'image']
                )->getUrl();
                if ($magentoImageUrl && strpos($magentoImageUrl, 'placeholder') === false) {
                    return $magentoImageUrl;
                }
            } catch (\Exception $e) {
                // Fall through to placeholder
            }
        }

        return $this->imageHelper->getDefaultPlaceholderUrl('small_image');
    }

    /**
     * Get product to use for image (parent for configurable children, same as spotlight.phtml)
     *
     * @param ProductModel $product
     * @return ProductModel
     */
    private function getProductForImage(ProductModel $product): ProductModel
    {
        $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
        if (!empty($parentIds)) {
            try {
                return $this->productRepository->getById((int) $parentIds[0]);
            } catch (\Exception $e) {
                return $product;
            }
        }
        return $product;
    }

    /**
     * Get placeholder image URL
     *
     * @return string
     */
    private function getPlaceholderImageUrl(): string
    {
        try {
            return $this->imageHelper->getDefaultPlaceholderUrl('image');
        } catch (\Exception $e) {
            // Fallback to a default placeholder path
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) 
                . 'catalog/product/placeholder/image.jpg';
        }
    }

    /**
     * Sanitize string to ensure valid JSON encoding
     *
     * @param string|null $string
     * @return string
     */
    private function sanitizeString(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        
        // Remove null bytes and control characters that break JSON
        $string = str_replace(["\0", "\x00"], '', $string);
        
        // Replace problematic control characters
        $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
        
        // Trim whitespace
        $string = trim($string);
        
        // Ensure UTF-8 encoding
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        }
        
        return $string;
    }

    /**
     * Validate and clean array before JSON encoding
     *
     * @param array $data
     * @return array
     */
    private function cleanArrayForJson(array $data): array
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            // Skip empty keys
            if ($key === '' || $key === null) {
                continue;
            }
            
            if ($value === null) {
                // Skip null values to keep JSON clean
                continue;
            } elseif (is_string($value)) {
                $sanitized = $this->sanitizeString($value);
                if ($sanitized !== '' || $key === 'description') {
                    $cleaned[$key] = $sanitized;
                }
            } elseif (is_array($value)) {
                // Keep aggregateRating and review even if empty (schema compatibility)
                if ($key === 'aggregateRating' || $key === 'review') {
                    $cleaned[$key] = $value;
                } else {
                    $cleanedArray = $this->cleanArrayForJson($value);
                    if (!empty($cleanedArray)) {
                        $cleaned[$key] = $cleanedArray;
                    }
                }
            } elseif (is_float($value)) {
                if (is_nan($value) || is_infinite($value)) {
                    $cleaned[$key] = 0.0;
                } else {
                    $cleaned[$key] = $value;
                }
            } elseif (is_numeric($value) || is_bool($value)) {
                $cleaned[$key] = $value;
            } else {
                // For other types, include as-is
                $cleaned[$key] = $value;
            }
        }
        
        return $cleaned;
    }

    /**
     * Get aggregate rating from Bazaarvoice database table
     *
     * @param ProductModel $product
     * @return array
     */
    private function getBazaarvoiceAggregateRating(ProductModel $product): array
    {
        $sku = $product->getSku();
        
        // Extract parent SKU if this is a child product
        $parentSku = $this->getParentSku($sku);
        
        $collection = $this->ratingsCollectionFactory->create()
            ->addFieldToFilter('sku', $parentSku);
        
        if ($rating = $collection->getFirstItem()) {
            if ($rating->getId() && $rating->getReviewsCount() > 0) {
                return [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (string)$rating->getAvgRatingValue(),
                    'reviewCount' => (string)$rating->getReviewsCount(),
                    'bestRating' => (string)$rating->getBestRating()
                ];
            }
        }
        
        return [];
    }

    /**
     * Get reviews from Bazaarvoice database table
     *
     * @param ProductModel $product
     * @return array
     */
    private function getBazaarvoiceReviews(ProductModel $product): array
    {
        $sku = $product->getSku();
        
        // Extract parent SKU if this is a child product
        $parentSku = $this->getParentSku($sku);
        
        $collection = $this->reviewsCollectionFactory->create()
            ->addFieldToFilter('sku', $parentSku)
            ->setOrder('rating_value', 'DESC')
            ->setOrder('date_published', 'DESC')
            ->setPageSize(5);
        
        // Use parent product for itemReviewed - Google requires Product (not ProductGroup), must match main schema @id
        $productForReviewed = $this->getProductForImage($product);
        $productUrl = $productForReviewed->getProductUrl();
        $productName = $this->sanitizeString($productForReviewed->getName());
        
        $itemReviewed = [
            '@type' => 'Product',
            '@id' => $productUrl . '#group',
            'name' => $productName
        ];
        
        $reviews = [];
        foreach ($collection as $review) {
            $reviewData = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $review->getAuthorName() ?: 'Anonymous'
                ],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (string)$review->getRatingValue(),
                    'bestRating' => (string)$review->getBestRating()
                ],
                'itemReviewed' => $itemReviewed
            ];
            
            // Add date if available
            if ($review->getDatePublished()) {
                try {
                    $date = new \DateTime($review->getDatePublished());
                    $reviewData['datePublished'] = $date->format('Y-m-d');
                } catch (\Exception $e) {
                    // Skip date if invalid
                }
            }
            
            // Add title if available
            if ($review->getReviewTitle()) {
                $reviewData['name'] = $review->getReviewTitle();
            }
            
            // Add body if available
            if ($review->getReviewBody()) {
                $reviewData['reviewBody'] = $review->getReviewBody();
            }
            
            $reviews[] = $reviewData;
        }
        
        return $reviews;
    }

    /**
     * Extract parent SKU from child SKU
     *
     * @param string $sku
     * @return string
     */
    private function getParentSku(string $sku): string
    {
        // Check if this is a child product (contains underscore)
        if (strpos($sku, '_') !== false) {
            // Extract parent SKU (everything before first underscore)
            return explode('_', $sku)[0];
        }
        
        return $sku;
    }
}
