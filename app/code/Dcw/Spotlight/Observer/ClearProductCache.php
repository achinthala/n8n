<?php

declare(strict_types=1);

namespace Dcw\Spotlight\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Dcw\Spotlight\Model\Cache\JsonLdCache;
use Dcw\Spotlight\Model\JsonLd\ProductInfo;
use Dcw\Spotlight\Helper\Config;
use Psr\Log\LoggerInterface;

/**
 * Clear and regenerate JSON-LD cache when product is saved
 */
class ClearProductCache implements ObserverInterface
{
    /**
     * @var JsonLdCache
     */
    private $jsonLdCache;
    
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
     * @var Config
     */
    private $config;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param JsonLdCache $jsonLdCache
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductInfo $productInfo
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonLdCache $jsonLdCache,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ProductInfo $productInfo,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->jsonLdCache = $jsonLdCache;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->productInfo = $productInfo;
        $this->config = $config;
        $this->logger = $logger;
    }
    
    /**
     * Clear and regenerate JSON-LD cache when product is saved
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            
            if (!$product || !$product->getId()) {
                return;
            }
            
            // Only process if cache is enabled
            if (!$this->config->isCacheEnabled()) {
                return;
            }
            
            $productId = (int)$product->getId();
            $productsToRegenerate = [$productId];
            
            // If this is a child product, also add parent products to regenerate
            $typeInstance = $product->getTypeInstance();
            if (method_exists($typeInstance, 'getParentIdsByChild')) {
                $parentIds = $typeInstance->getParentIdsByChild($productId);
                if (!empty($parentIds)) {
                    $productsToRegenerate = array_merge($productsToRegenerate, $parentIds);
                }
            }
            
            // Clear and regenerate cache for each product in all stores
            foreach ($this->storeManager->getStores() as $store) {
                $storeId = (int)$store->getId();
                
                // Skip if variants not enabled for this store
                if (!$this->config->isVariantsEnabled($storeId)) {
                    continue;
                }
                
                foreach ($productsToRegenerate as $prodId) {
                    try {
                        // Clear existing cache
                        $this->jsonLdCache->clearProduct($prodId);
                        
                        // Load product for this store
                        $fullProduct = $this->productRepository->getById($prodId, false, $storeId);
                        
                        // Only regenerate for configurable products
                        if ($fullProduct->getTypeId() === 'configurable') {
                            // Generate fresh JSON-LD
                            $jsonLdData = $this->productInfo->extract($fullProduct);
                            
                            // Save to cache (extract method already does this, but being explicit)
                            // The extract method will save to cache automatically
                            
                            $this->logger->info("Regenerated JSON-LD cache for product {$prodId} in store {$storeId}");
                        }
                    } catch (\Exception $e) {
                        $this->logger->error("Error regenerating cache for product {$prodId}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in JSON-LD cache regeneration observer: ' . $e->getMessage());
        }
    }
}

