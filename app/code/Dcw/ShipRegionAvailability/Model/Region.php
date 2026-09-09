<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model;

use Dcw\ShipRegionAvailability\Api\Data\RegionInterface;
use Magento\Framework\Model\AbstractModel;

class Region extends AbstractModel implements RegionInterface
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Region::class);
    }

    public function getRegionId(): ?int
    {
        $value = $this->getData(self::REGION_ID);
        return $value !== null ? (int) $value : null;
    }

    public function setRegionId(int $regionId): RegionInterface
    {
        return $this->setData(self::REGION_ID, $regionId);
    }

    public function getCode(): ?string
    {
        $value = $this->getData(self::CODE);
        return $value !== null ? (string) $value : null;
    }

    public function setCode(string $code): RegionInterface
    {
        return $this->setData(self::CODE, $code);
    }

    public function getName(): ?string
    {
        $value = $this->getData(self::NAME);
        return $value !== null ? (string) $value : null;
    }

    public function setName(string $name): RegionInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    public function setStatus(int $status): RegionInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): RegionInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }
}
