<?php

namespace Narvar\Accord\Helper;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\CacheInterface;
 
class CacheHelper
{
 
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CacheInterface
     */
    private $cache;
 
    /**
     * CacheHelper constructor.
     * @param CacheInterface $cache
     * @param SerializerInterface $serialized
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->cache        = $cache;
        $this->serializer   = $serializer;
    }
 
    public function loadDataFromCache($cacheId)
    {
        $data = $this->cache->load($cacheId);
        if (!$data) {
            return null;
        }
        return $this->serializer->unserialize($data);
    }
 
    public function saveDataInCache($cacheId, $data)
    {
        $serializedData = $this->serializer->serialize($data);
        $this->cache->save($serializedData, $cacheId);
    }
}
