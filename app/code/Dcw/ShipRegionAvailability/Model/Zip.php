<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model;

use Dcw\ShipRegionAvailability\Api\Data\ZipInterface;
use Magento\Framework\Model\AbstractModel;

class Zip extends AbstractModel implements ZipInterface
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Zip::class);
    }

    public function getZipId(): ?int
    {
        $value = $this->getData(self::ZIP_ID);
        return $value !== null ? (int) $value : null;
    }

    public function setZipId(int $zipId): ZipInterface
    {
        return $this->setData(self::ZIP_ID, $zipId);
    }

    public function getZipCode(): ?string
    {
        $value = $this->getData(self::ZIP_CODE);
        return $value !== null ? (string) $value : null;
    }

    public function setZipCode(string $zipCode): ZipInterface
    {
        return $this->setData(self::ZIP_CODE, $zipCode);
    }

    public function getRegionId(): int
    {
        return (int) $this->getData(self::REGION_ID);
    }

    public function setRegionId(int $regionId): ZipInterface
    {
        return $this->setData(self::REGION_ID, $regionId);
    }

    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    public function setStatus(int $status): ZipInterface
    {
        return $this->setData(self::STATUS, $status);
    }
}
