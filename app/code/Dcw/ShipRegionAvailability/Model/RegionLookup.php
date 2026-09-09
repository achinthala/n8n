<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model;

use Dcw\ShipRegionAvailability\Api\Data\RegionInterface;
use Dcw\ShipRegionAvailability\Api\RegionLookupInterface;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Region as RegionResource;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip as ZipResource;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

class RegionLookup implements RegionLookupInterface
{
    private const CACHE_TAG = 'dcw_ship_region_zip';
    private const CACHE_PREFIX = 'dcw_ship_zip_';
    private const CACHE_LIFETIME = 86400;

    public function __construct(
        private readonly \Magento\Framework\App\ResourceConnection $resourceConnection,
        private readonly RegionFactory $regionFactory,
        private readonly RegionResource $regionResource,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer
    ) {
    }

    public function normalizeZipCode(string $zipCode): ?string
    {
        $digits = preg_replace('/\D/', '', $zipCode) ?? '';
        if (strlen($digits) >= 5) {
            return substr($digits, 0, 5);
        }
        return null;
    }

    public function getRegionByZip(string $zipCode): ?RegionInterface
    {
        $normalized = $this->normalizeZipCode($zipCode);
        if ($normalized === null) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . $normalized;
        $cached = $this->cache->load($cacheKey);
        if ($cached !== false) {
            $data = $this->serializer->unserialize($cached);
            if (empty($data)) {
                return null;
            }
            $region = $this->regionFactory->create();
            $region->setData($data);
            return $region;
        }

        $connection = $this->resourceConnection->getConnection();
        $zipTable = $this->resourceConnection->getTableName(ZipResource::TABLE_NAME);
        $regionTable = $this->resourceConnection->getTableName(RegionResource::TABLE_NAME);

        $select = $connection->select()
            ->from(['z' => $zipTable], [])
            ->join(
                ['r' => $regionTable],
                'z.region_id = r.region_id',
                ['region_id', 'code', 'name', 'status', 'sort_order']
            )
            ->where('z.zip_code = ?', $normalized)
            ->where('z.status = ?', Config::STATUS_ENABLED)
            ->where('r.status = ?', Config::STATUS_ENABLED)
            ->limit(1);

        $row = $connection->fetchRow($select);
        if (!$row) {
            $this->cache->save(
                $this->serializer->serialize([]),
                $cacheKey,
                [self::CACHE_TAG],
                self::CACHE_LIFETIME
            );
            return null;
        }

        $this->cache->save(
            $this->serializer->serialize($row),
            $cacheKey,
            [self::CACHE_TAG],
            self::CACHE_LIFETIME
        );

        $region = $this->regionFactory->create();
        $region->setData($row);
        return $region;
    }
}
