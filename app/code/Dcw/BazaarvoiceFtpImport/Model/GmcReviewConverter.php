<?php

/**
 * GMC Review Converter
 * Converts Bazaarvoice XML to Google Merchant Center format
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class GmcReviewConverter
{
    const XML_PATH_GMC_ENABLED = 'bazaarvoice_ftp/gmc_settings/enabled';
    const XML_PATH_GMC_PUBLISHER_NAME = 'bazaarvoice_ftp/gmc_settings/publisher_name';
    const XML_PATH_GMC_AGGREGATOR_NAME = 'bazaarvoice_ftp/gmc_settings/aggregator_name';
    const XML_PATH_GMC_REVIEW_STATUSES = 'bazaarvoice_ftp/gmc_settings/review_statuses';
    const XML_PATH_GMC_DEFAULT_RATING = 'bazaarvoice_ftp/gmc_settings/default_rating';
    const XML_PATH_GMC_RATING_RANGE = 'bazaarvoice_ftp/gmc_settings/rating_range';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ReviewTextSanitizer
     */
    private $reviewTextSanitizer;

    /**
     * GmcReviewConverter constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurableType
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param File $fileDriver
     * @param Filesystem $filesystem
     * @param ReviewTextSanitizer $reviewTextSanitizer
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Configurable $configurableType,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        File $fileDriver,
        Filesystem $filesystem,
        ReviewTextSanitizer $reviewTextSanitizer
    ) {
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->fileDriver = $fileDriver;
        $this->filesystem = $filesystem;
        $this->reviewTextSanitizer = $reviewTextSanitizer;
    }

    /**
     * Check if GMC conversion is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_GMC_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Convert Bazaarvoice XML to GMC format
     *
     * @param string $inputFile
     * @param int|null $storeId
     * @return string|null
     * @throws LocalizedException
     */
    public function convertToGmc(string $inputFile, $storeId = null): ?string
    {

        if(!$this->isEnabled($storeId)) {
            $this->logger->info('GMC conversion is disabled, skipping');
            return null;
        }

        $this->logger->info('Starting GMC review conversion for: ' . $inputFile);

        // Load and parse Bazaarvoice XML
        $xml = $this->loadBazaarvoiceXml($inputFile);

        if(!$xml) {
            throw new LocalizedException(__('Failed to parse Bazaarvoice XML file: %1', $inputFile));
        }

        // Process reviews
        $skuReviews = $this->processReviews($xml, $storeId);

        if(empty($skuReviews)) {
            $this->logger->info('No reviews found to process');
            return null;
        }

        // Handle configurable products
        $skuReviews = $this->processConfigurableProducts($skuReviews, $storeId);

        // Generate GMC XML
        $outputFile = $this->generateGmcXml($skuReviews, $storeId);

        $this->logger->info('GMC conversion completed. Output file: ' . $outputFile);
        return $outputFile;
    }

    /**
     * Load and parse Bazaarvoice XML
     *
     * @param string $inputFile
     * @return \SimpleXMLElement|null
     * @throws LocalizedException
     */
    private function loadBazaarvoiceXml(string $inputFile): ?\SimpleXMLElement
    {
        if(!$this->fileDriver->isExists($inputFile)) {
            throw new LocalizedException(__('Bazaarvoice XML file not found: %1', $inputFile));
        }

        $raw = $this->fileDriver->fileGetContents($inputFile);

        // Remove BOM if present
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        // Remove any content before the first < character
        $raw = preg_replace('/^[^<]*/', '', $raw);

        // Enable internal error handling
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($raw);

        if(!$xml) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(function($error) {
                return $error->message;
            }, $errors);
            throw new LocalizedException(__('Failed to parse XML: %1', implode(', ', $errorMessages)));
        }

        // Register namespace
        $xml->registerXPathNamespace('bv', 'http://www.bazaarvoice.com/xs/PRR/StandardClientFeed/14.8');

        return $xml;
    }

    /**
     * Process reviews from XML
     *
     * @param \SimpleXMLElement $xml
     * @param int|null $storeId
     * @return array
     */
    private function processReviews(\SimpleXMLElement $xml, $storeId = null): array
    {
        $skuReviews = [];
        $allowedStatuses = $this->getAllowedReviewStatuses();


        foreach($xml->xpath('//bv:Product') as $product) {
            $sku = trim((string)$product['id']);

            // Google guidance (FINC-303): keep sample product reviews in the feed flagged as spam instead of excluding them
            $isSampleProduct = $this->isSampleSku($sku);

            $productUrl = $this->findFirstText($product, ['ProductPageUrl']);
            $product->registerXPathNamespace('bv', 'http://www.bazaarvoice.com/xs/PRR/StandardClientFeed/14.8');

            foreach ($product->xpath('./bv:Reviews/bv:Review') ?: [] as $review) {
                $status = strtolower($this->findFirstText($review, ['ModerationStatus']));
                if (!in_array($status, $allowedStatuses)) {
                    continue;
                }

                $displayName = $this->reviewTextSanitizer->decodeHtmlEntities(
                    $this->findFirstText($review, ['DisplayName', 'ReviewerNickname'])
                );
                // Google guidance (FINC-303): anonymous reviews stay in the feed flagged as spam instead of being excluded
                $isAnonymous = ($displayName === '' || strcasecmp($displayName, 'Anonymous') === 0);

                // Extract title and content; strip URLs, sales agent names, and reviewer self-reference
                $title = $this->reviewTextSanitizer->sanitizeReviewText(
                    trim($this->findFirstText($review, ['Title'])),
                    $displayName
                );
                $content = $this->reviewTextSanitizer->sanitizeReviewText(
                    trim($this->findFirstText($review, ['ReviewText', 'ReviewBody'])),
                    $displayName
                );
                $rating = trim($this->findFirstText($review, ['Rating', 'OverallRating']));

                // Google guidance (FINC-303): reviews with missing title/content stay in the feed flagged as spam
                $isMissingText = $this->isMissingReviewText($title) || $this->isMissingReviewText($content);

                $isSpam = $isSampleProduct || $isAnonymous || $isMissingText;
                if ($isSpam) {
                    $this->logger->info(
                        'Flagging review as spam for SKU ' . $sku
                        . ' (sample: ' . (int)$isSampleProduct
                        . ', anonymous: ' . (int)$isAnonymous
                        . ', missing text: ' . (int)$isMissingText . ')'
                    );
                }

                $rid = trim((string)$review['id']) ?: $this->findFirstText($review, ['ReviewID']);
                $skuReviews[$sku][$rid] = [
                    'review_id' => $rid,
                    'display_name' => $displayName,
                    'is_anonymous' => $isAnonymous,
                    'is_spam' => $isSpam,
                    'timestamp' => $this->findFirstText($review, ['FirstPublishTime', 'SubmissionTime']),
                    'title' => $title,
                    'content' => $content,
                    'url' => $productUrl,
                    'review_deep_link' => $this->findFirstText($review, ['ProductReviewsDeepLinkedUrl']),
                    'rating' => $rating,
                    'rating_range' => $this->findFirstText($review, ['RatingRange']) ?: $this->getDefaultRatingRange(),
                    'skus' => [$sku]
                ];
            }

        }

        return $skuReviews;
    }

    /**
     * Check whether a SKU belongs to a sample product.
     *
     * @param string $sku
     * @return bool
     */
    private function isSampleSku(string $sku): bool
    {
        return $sku !== '' && str_starts_with(strtolower($sku), 's');
    }

    /**
     * Resolve the parent SKU for feed output (configurable parent or standalone simple).
     *
     * @param string $sku
     * @param int|null $storeId
     * @return string|null Parent SKU when found in Magento, null otherwise
     */
    private function resolveParentSku(string $sku, $storeId = null): ?string
    {
        if ($sku === '') {
            return null;
        }

        try {
            $product = $this->productRepository->get($sku, false, $storeId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to resolve parent SKU for ' . $sku . ': ' . $e->getMessage());
            return null;
        }

        if ($product->getTypeId() === 'configurable') {
            return $sku;
        }

        $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
        if (empty($parentIds)) {
            return $sku;
        }

        try {
            $parent = $this->productRepository->getById((int) $parentIds[0], false, $storeId);

            return $parent->getSku();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            $this->logger->warning(
                'Failed to resolve configurable parent for child SKU ' . $sku . ': ' . $e->getMessage()
            );
            return null;
        }
    }

    /**
     * Process configurable products and assign reviews to children
     *
     * @param array $skuReviews
     * @param int|null $storeId
     * @return array
     */
    private function processConfigurableProducts(array $skuReviews, $storeId = null): array
    {
        $newSkuReviews = [];


        foreach($skuReviews as $sku => $reviews) {
            $parentSku = $this->resolveParentSku($sku, $storeId);
            if ($parentSku === null) {
                $this->logger->info('Skipping reviews: parent SKU not found in Magento: ' . $sku);
                continue;
            }

            try {
                $product = $this->productRepository->get($parentSku, false, $storeId);
                $isConfigurable = ($product->getTypeId() === 'configurable');

                if ($isConfigurable) {
                    // Get all active children
                    $childIds = $this->configurableType->getChildrenIds($product->getId())[0] ?? [];
                    $activeChildrenSkus = $this->getActiveChildrenSkus($childIds);

                    // Live product page whose variants are all disabled: keep the reviews mapped
                    // to the parent SKU so the feed matches what is visible on the product page
                    if (empty($activeChildrenSkus)
                        && (int)$product->getStatus() === \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                    ) {
                        $this->logger->info('No active children for enabled product ' . $parentSku . '; mapping reviews to the parent SKU');
                        $activeChildrenSkus = [$parentSku];
                    }

                    foreach ($reviews as $rid => $review) {
                        $review['skus'] = $activeChildrenSkus;
                        $newSkuReviews[$parentSku][$rid] = $review;
                    }

                } else {
                    foreach ($reviews as $rid => $review) {
                        $review['skus'] = [$parentSku];
                        $newSkuReviews[$parentSku][$rid] = $review;
                    }
                }

            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->info('Skipping reviews: parent SKU not found in Magento: ' . $parentSku);
                continue;
            }

        }

        // Remove empty review entries

        foreach($newSkuReviews as $sku => &$reviews) {
            foreach ($reviews as $rid => &$review) {
                if (empty($review['skus'])) {
                    unset($reviews[$rid]);
                }

            }

            if (empty($reviews)) {
                unset($newSkuReviews[$sku]);
            }

        }

        unset($reviews);

        return $newSkuReviews;
    }

    /**
     * Get active children SKUs
     *
     * @param array $childIds
     * @return array
     */
    private function getActiveChildrenSkus(array $childIds): array
    {
        $activeChildrenSkus = [];


        foreach($childIds as $childId) {
            try {
                $child = $this->productRepository->getById($childId);
                if ($child->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    $activeChildrenSkus[] = (string)$child->getSku();
                }

            } catch (\Exception $e) {
                $this->logger->warning('Failed to load child product: ' . $childId . ' - ' . $e->getMessage());
            }

        }

        return $activeChildrenSkus;
    }

    /**
     * Generate GMC XML file
     *
     * @param array $skuReviews
     * @param int|null $storeId
     * @return string
     * @throws LocalizedException
     */
    private function generateGmcXml(array $skuReviews, $storeId = null): string
    {
        $outputDir = $this->getOutputDirectory($storeId);
        
        // Convert relative path to absolute path
        if (!str_starts_with($outputDir, '/')) {
            $outputDir = BP . '/' . ltrim($outputDir, '/');
        }
        
        $outputFile = $outputDir . '/gmc_bazaarvoice_reviews_feed.xml';

        // Create output directory if it doesn't exist
        if(!$this->fileDriver->isExists($outputDir)) {
            $this->fileDriver->createDirectory($outputDir, 0755);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Create root element
        $feed = $dom->createElement('feed');
        $feed->setAttribute('xmlns:vc', 'http://www.w3.org/2007/XMLSchema-versioning');
        $feed->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $feed->setAttribute('xsi:noNamespaceSchemaLocation',
            'http://www.google.com/shopping/reviews/schema/product/2.4/product_reviews.xsd');
        $dom->appendChild($feed);

        // Add version
        $feed->appendChild($dom->createElement('version', '2.4'));

        // Add aggregator
        $agg = $dom->createElement('aggregator');
        $aggNameEl = $dom->createElement('name');
        $aggNameEl->appendChild($dom->createCDATASection($this->getAggregatorName($storeId)));
        $agg->appendChild($aggNameEl);
        $feed->appendChild($agg);

        // Add publisher
        $pub = $dom->createElement('publisher');
        $pubNameEl = $dom->createElement('name');
        $pubNameEl->appendChild($dom->createCDATASection($this->getPublisherName($storeId)));
        $pub->appendChild($pubNameEl);
        $feed->appendChild($pub);

        // Add reviews
        $reviewsNode = $dom->createElement('reviews');
        $feed->appendChild($reviewsNode);

        $reviewCount = 0;

        foreach ($skuReviews as $skuKey => $reviews) {
            if ($this->resolveParentSku((string) $skuKey, $storeId) === null) {
                $this->logger->info('Skipping reviews: parent SKU not found in Magento: ' . $skuKey);
                continue;
            }

            foreach ($reviews as $r) {
                $reviewerName = trim((string)($r['display_name'] ?? ''));
                if (empty($r['skus'])) {
                    $this->logger->info('Skipping review with no product SKUs for SKU: ' . $skuKey);
                    continue;
                }

                $reviewEl = $dom->createElement('review');
                $reviewEl->appendChild($dom->createElement('review_id', $r['review_id']));

                // Reviewer (schema requires a non-empty name; anonymous reviewers carry the is_anonymous attribute)
                $revName = $dom->createElement('reviewer');
                $nameEl = $dom->createElement('name');
                $nameEl->appendChild($dom->createCDATASection($reviewerName !== '' ? $reviewerName : 'Anonymous'));
                if (!empty($r['is_anonymous'])) {
                    $nameEl->setAttribute('is_anonymous', 'true');
                }
                $revName->appendChild($nameEl);
                $reviewEl->appendChild($revName);

                // Timestamp
                $reviewEl->appendChild($dom->createElement('review_timestamp', $r['timestamp']));

                // Title is optional in the schema; omit it when missing
                if (!$this->isMissingReviewText((string)($r['title'] ?? ''))) {
                    $titleEl = $dom->createElement('title');
                    $titleEl->appendChild($dom->createCDATASection($r['title']));
                    $reviewEl->appendChild($titleEl);
                }

                // Content is required and must be non-empty; Bazaarvoice uses literal "0" for missing text
                $contentValue = $this->isMissingReviewText((string)($r['content'] ?? '')) ? '0' : $r['content'];
                $contentEl = $dom->createElement('content');
                $contentEl->appendChild($dom->createCDATASection($contentValue));
                $reviewEl->appendChild($contentEl);

                // Review URL is required by the schema; fall back to the Bazaarvoice review deep link
                // when the product has no page URL (e.g. sample products)
                $reviewUrl = $r['url'] ?: (string)($r['review_deep_link'] ?? '');
                if ($reviewUrl) {
                    $urlEl = $dom->createElement('review_url', $reviewUrl);
                    $urlEl->setAttribute('type', $r['url'] ? 'group' : 'singleton');
                    $reviewEl->appendChild($urlEl);
                }

                // Ratings
                $ratingsEl = $dom->createElement('ratings');
                $overall = $dom->createElement('overall', $r['rating'] ?: $this->getDefaultRating($storeId));
                $overall->setAttribute('min', '1');
                $overall->setAttribute('max', $r['rating_range']);
                $ratingsEl->appendChild($overall);
                $reviewEl->appendChild($ratingsEl);

                // Products
                $productsEl = $dom->createElement('products');
                foreach ($r['skus'] as $sku) {
                    $p = $dom->createElement('product');
                    
                    // Get product information
                    $productInfo = $this->getProductInfo($sku, $storeId);
                    
                    // Product IDs section (schema order: gtins, mpns, skus, brands, asins; item_group_ids is not part of the schema)
                    $productIdsEl = $dom->createElement('product_ids');

                    // MPNs (child / variant SKU)
                    $mpnsEl = $dom->createElement('mpns');
                    $mpnEl = $dom->createElement('mpn', $productInfo['mpn']);
                    $mpnsEl->appendChild($mpnEl);
                    $productIdsEl->appendChild($mpnsEl);

                    // SKUs (child / variant SKU so Google can match the offer ids in the product feed)
                    $skusEl = $dom->createElement('skus');
                    $skuEl = $dom->createElement('sku', (string) $sku);
                    $skusEl->appendChild($skuEl);
                    $productIdsEl->appendChild($skusEl);

                    // Brands
                    $brandsEl = $dom->createElement('brands');
                    $brandEl = $dom->createElement('brand', $productInfo['brand']);
                    $brandsEl->appendChild($brandEl);
                    $productIdsEl->appendChild($brandsEl);
                    
                    $p->appendChild($productIdsEl);

                    // product_url is required by the schema; fall back to the Magento product URL,
                    // then to the Bazaarvoice review deep link (e.g. sample products without a page URL)
                    $productUrl = $r['url'] ?: ($productInfo['product_url'] ?? '');
                    if ($productUrl === '') {
                        $productUrl = (string)($r['review_deep_link'] ?? '');
                    }

                    if (!$r['url'] && $productInfo['name'] !== '') {
                        $productNameEl = $dom->createElement('product_name');
                        $productNameEl->appendChild($dom->createCDATASection($productInfo['name']));
                        $p->appendChild($productNameEl);
                    }

                    if ($productUrl !== '') {
                        $p->appendChild($dom->createElement('product_url', $productUrl));
                    }

                    $productsEl->appendChild($p);
                }

                $reviewEl->appendChild($productsEl);

                // Google guidance (FINC-303): previously filtered reviews are included and flagged as spam
                if (!empty($r['is_spam'])) {
                    $reviewEl->appendChild($dom->createElement('is_spam', 'true'));
                }

                $reviewsNode->appendChild($reviewEl);
                $reviewCount++;
            }

        }

        // Save file
        $this->fileDriver->filePutContents($outputFile, $dom->saveXML());

        $this->logger->info("Generated GMC XML with {$reviewCount} reviews: " . $outputFile);

        return $outputFile;
    }

    /**
     * Bazaarvoice uses literal "0" for missing title/body; PHP also treats "0" as empty in if ($value).
     *
     * @param string $value
     * @return bool
     */
    private function isMissingReviewText(string $value): bool
    {
        $value = trim($value);

        return $value === '' || $value === '0';
    }

    /**
     * Find first non-empty text from multiple possible element names
     *
     * @param \SimpleXMLElement $el
     * @param array $names
     * @return string
     */
    private function findFirstText(\SimpleXMLElement $el, array $names): string
    {
        foreach($names as $name) {
            if (isset($el->$name) && trim((string)$el->$name) !== '') {
                return trim((string)$el->$name);
            }
        }

        return '';
    }

    /**
     * Get allowed review statuses
     *
     * @return array
     */
    private function getAllowedReviewStatuses(): array
    {
        $statuses = $this->scopeConfig->getValue(self::XML_PATH_GMC_REVIEW_STATUSES);

        if(empty($statuses)) {
            return ['approved', 'published'];
        }

        return array_map('trim', explode(',', $statuses));
    }

    /**
     * Get output directory - always use media folder
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOutputDirectory($storeId = null): string
    {
        try {
            // Always use pub/media for media files
            $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            return $mediaPath . 'bazaarvoice';
        } catch (\Exception $e) {
            $this->logger->error('Could not access media directory: ' . $e->getMessage());
            // Fallback to relative path if absolute path fails
            return 'pub/media/bazaarvoice';
        }
    }



    /**
     * Get publisher name
     *
     * @param int|null $storeId
     * @return string
     */
    private function getPublisherName($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GMC_PUBLISHER_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'FlooringInc';
    }

    /**
     * Get aggregator name
     *
     * @param int|null $storeId
     * @return string
     */
    private function getAggregatorName($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GMC_AGGREGATOR_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'Bazaarvoice';
    }

    /**
     * Get default rating
     *
     * @param int|null $storeId
     * @return string
     */
    private function getDefaultRating($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GMC_DEFAULT_RATING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '5';
    }

    /**
     * Get default rating range
     *
     * @param int|null $storeId
     * @return string
     */
    private function getDefaultRatingRange($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GMC_RATING_RANGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '5';
    }

    /**
     * Get product information (MPN and Brand)
     *
     * @param string $sku
     * @param int|null $storeId
     * @return array
     */
    private function getProductInfo(string $sku, $storeId = null): array
    {
        try {
            $product = $this->productRepository->get($sku, false, $storeId);
            $brandName = "Flooring Inc";
            // $categoryIds = $product->getCategoryIds();

            // if (!empty($categoryIds)) {
            //     $category = $this->getFirstEnabledCategory($categoryIds, $storeId);
            //     if ($category) {
            //         if ($category->getData('is_brand') == 1) {
            //             $brandName = $category->getName();
            //         }
            //     }
            // }
            
            return [
                'mpn' => $sku,
                'brand' => $brandName,
                'name' => trim((string)$product->getName()),
                'product_url' => method_exists($product, 'getProductUrl') ? trim((string)$product->getProductUrl()) : ''
            ];

        } catch (\Exception $e) {
            $this->logger->warning('Failed to get product info for SKU: ' . $sku . ' - ' . $e->getMessage());

            // Return defaults if product not found
            return [
                'mpn' => $sku,
                'brand' => 'Flooring Inc',
                'name' => '',
                'product_url' => ''
            ];
        }
    }

    /**
     * Get the first enabled category from the provided category IDs.
     *
     * @param array $categoryIds
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getFirstEnabledCategory($categoryIds, $storeId)
    {
        if (empty($categoryIds)) {
            return null;
        }

        // Load categories with the specified IDs and filter by active status
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect(['name', 'is_active', 'url_path', 'display_mode'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter('is_active', 1);

        foreach ($categoryIds as $categoryId) {
            $category = $collection->getItemById($categoryId);
            
            if ($category && $category->getDisplayMode() !== 'PAGE') {
                return $this->categoryRepositoryInterface->get($categoryId, $storeId);
            }
        }

        return null;
    }
}
