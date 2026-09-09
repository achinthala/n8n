<?php

declare(strict_types=1);

namespace Dcw\Spotlight\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Dcw\Spotlight\Model\JsonLd\ProductInfo;
use Dcw\Spotlight\Model\Cache\JsonLdCache;
use Dcw\Spotlight\Helper\Config;
use Psr\Log\LoggerInterface;

/**
 * Warm JSON-LD cache for configurable products
 * 
 * Includes:
 * - Product information
 * - Offers and pricing
 * - Brand information
 * - Bazaarvoice aggregate ratings (from dcw_spotlight_product_ratings table)
 * - Bazaarvoice top 5 reviews (from dcw_spotlight_top_reviews table)
 * - Variant data
 */
class WarmJsonLdCache
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var ProductInfo
     */
    private $productInfo;
    
    /**
     * @var JsonLdCache
     */
    private $jsonLdCache;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var State
     */
    private $state;
    
    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductInfo $productInfo
     * @param JsonLdCache $jsonLdCache
     * @param Config $config
     * @param LoggerInterface $logger
     * @param State $state
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ProductInfo $productInfo,
        JsonLdCache $jsonLdCache,
        Config $config,
        LoggerInterface $logger,
        State $state
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->productInfo = $productInfo;
        $this->jsonLdCache = $jsonLdCache;
        $this->config = $config;
        $this->logger = $logger;
        $this->state = $state;
    }
    
    /**
     * Execute cron job to warm cache
     *
     * @return void
     */
    public function execute(): void
    {
        $startTime = microtime(true);
        $processedCount = 0;
        $errorCount = 0;
        
        $this->logger->info('[JSON-LD Cache Warm] Starting cache warming process (includes Bazaarvoice reviews)');
        
        try {
            // Set area code for cron execution
            try {
                $this->state->setAreaCode(Area::AREA_FRONTEND);
            } catch (\Exception $e) {
                // Area already set, continue
            }
            
            foreach ($this->storeManager->getStores() as $store) {
                $storeId = (int)$store->getId();
                
                // Skip if cache is not enabled for this store
                if (!$this->config->isCacheEnabled($storeId) || !$this->config->isVariantsEnabled($storeId)) {
                    continue;
                }
                
                $this->logger->info("[JSON-LD Cache Warm] Processing store: {$store->getName()} (ID: {$storeId})");
                
                // Get all configurable products
                $collection = $this->productCollectionFactory->create();
                $collection->addAttributeToSelect('*')
                    ->addStoreFilter($storeId)
                    ->addAttributeToFilter('type_id', ConfigurableType::TYPE_CODE)
                    ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                
                $totalProducts = $collection->getSize();
                $this->logger->info("[JSON-LD Cache Warm] Found {$totalProducts} configurable products in store {$storeId}");
                
                foreach ($collection as $product) {
                    try {
                        // Load full product
                        $fullProduct = $this->productRepository->getById(
                            $product->getId(),
                            false,
                            $storeId
                        );
                        
                        // Clear existing cache to force regeneration
                        $this->jsonLdCache->clearProduct((int)$fullProduct->getId());
                        
                        // Generate fresh JSON-LD data (includes Bazaarvoice reviews from database)
                        // This will:
                        // 1. Query dcw_spotlight_product_ratings for aggregate rating
                        // 2. Query dcw_spotlight_top_reviews for top 5 reviews
                        // 3. Build complete JSON-LD schema with reviews
                        // 4. Save to cache automatically
                        $jsonLdData = $this->productInfo->extract($fullProduct);
                        
                        $processedCount++;
                        
                        // Log progress every 100 products
                        if ($processedCount % 100 === 0) {
                            $this->logger->info("[JSON-LD Cache Warm] Processed {$processedCount} products so far (with reviews)...");
                        }
                        
                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->logger->error(
                            "[JSON-LD Cache Warm] Error processing product {$product->getId()}: " . $e->getMessage()
                        );
                    }
                }
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->logger->info(
                "[JSON-LD Cache Warm] Cache warming completed. " .
                "Processed: {$processedCount} products (with Bazaarvoice aggregateRating + reviews), " .
                "Errors: {$errorCount}, Duration: {$duration}s"
            );
            
        } catch (\Exception $e) {
            $this->logger->error('[JSON-LD Cache Warm] Fatal error in cache warming: ' . $e->getMessage());
        }
    }
}


