<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Setup\Patch\Data;

use Dcw\ShipRegionAvailability\Api\RegionRepositoryInterface;
use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\RegionFactory;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Seeds default regions from client ZIP sheet (Midwest, Northeast, South, West).
 */
class SeedDefaultShipRegions implements DataPatchInterface
{
    private const REGIONS = [
        ['code' => 'midwest', 'name' => 'Midwest', 'sort_order' => 10],
        ['code' => 'northeast', 'name' => 'Northeast', 'sort_order' => 20],
        ['code' => 'south', 'name' => 'South', 'sort_order' => 30],
        ['code' => 'west', 'name' => 'West', 'sort_order' => 40],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly RegionFactory $regionFactory,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $existingCodes = [];
        foreach ($this->collectionFactory->create() as $region) {
            $existingCodes[strtolower((string) $region->getCode())] = true;
        }

        foreach (self::REGIONS as $row) {
            if (isset($existingCodes[$row['code']])) {
                continue;
            }

            $region = $this->regionFactory->create();
            $region->setCode($row['code']);
            $region->setName($row['name']);
            $region->setStatus(Config::STATUS_ENABLED);
            $region->setSortOrder($row['sort_order']);
            $this->regionRepository->save($region);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
