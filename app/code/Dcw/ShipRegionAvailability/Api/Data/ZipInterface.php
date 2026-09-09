<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Api\Data;

interface ZipInterface
{
    public const ZIP_ID = 'zip_id';
    public const ZIP_CODE = 'zip_code';
    public const REGION_ID = 'region_id';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getZipId(): ?int;

    public function setZipId(int $zipId): ZipInterface;

    public function getZipCode(): ?string;

    public function setZipCode(string $zipCode): ZipInterface;

    public function getRegionId(): int;

    public function setRegionId(int $regionId): ZipInterface;

    public function getStatus(): int;

    public function setStatus(int $status): ZipInterface;
}
