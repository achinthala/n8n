<?php

declare(strict_types=1);

namespace Dcw\Spotlight\Model\Cache;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Dcw\Spotlight\Helper\Config;
use Psr\Log\LoggerInterface;

class JsonLdCache
{
    const CACHE_TAG = 'dcw_spotlight_jsonld';
    const CACHE_PREFIX = 'dcw_spotlight_jsonld_';
    
    /**
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->config = $config;
        $this->logger = $logger;
    }
    
    /**
     * Get cached JSON-LD data for a product
     *
     * @param int $productId
     * @param int $storeId
     * @return array|null
     */
    public function get(int $productId, int $storeId): ?array
    {
        if (!$this->config->isCacheEnabled($storeId)) {
            return null;
        }
        
        $cacheKey = $this->getCacheKey($productId, $storeId);
        $cachedData = $this->cache->load($cacheKey);
        
        if ($cachedData) {
            try {
                return $this->serializer->unserialize($cachedData);
            } catch (\Exception $e) {
                $this->logger->error('Error unserializing JSON-LD cache: ' . $e->getMessage());
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Save JSON-LD data to cache
     *
     * @param int $productId
     * @param int $storeId
     * @param array $data
     * @return bool
     */
    public function save(int $productId, int $storeId, array $data): bool
    {
        if (!$this->config->isCacheEnabled($storeId)) {
            return false;
        }
        
        try {
            $cacheKey = $this->getCacheKey($productId, $storeId);
            $lifetime = $this->config->getCacheLifetime($storeId);
            $serializedData = $this->serializer->serialize($data);
            
            return $this->cache->save(
                $serializedData,
                $cacheKey,
                [self::CACHE_TAG, self::CACHE_TAG . '_' . $productId],
                $lifetime > 0 ? $lifetime : null
            );
        } catch (\Exception $e) {
            $this->logger->error('Error saving JSON-LD to cache: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear cache for a specific product
     *
     * @param int $productId
     * @return bool
     */
    public function clearProduct(int $productId): bool
    {
        try {
            return $this->cache->clean([self::CACHE_TAG . '_' . $productId]);
        } catch (\Exception $e) {
            $this->logger->error('Error clearing product JSON-LD cache: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all JSON-LD cache
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        try {
            return $this->cache->clean([self::CACHE_TAG]);
        } catch (\Exception $e) {
            $this->logger->error('Error clearing all JSON-LD cache: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cache key for product
     *
     * @param int $productId
     * @param int $storeId
     * @return string
     */
    private function getCacheKey(int $productId, int $storeId): string
    {
        return self::CACHE_PREFIX . $productId . '_' . $storeId;
    }
}

