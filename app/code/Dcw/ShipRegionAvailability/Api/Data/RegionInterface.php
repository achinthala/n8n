<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Api\Data;

interface RegionInterface
{
    public const REGION_ID = 'region_id';
    public const CODE = 'code';
    public const NAME = 'name';
    public const STATUS = 'status';
    public const SORT_ORDER = 'sort_order';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getRegionId(): ?int;

    public function setRegionId(int $regionId): RegionInterface;

    public function getCode(): ?string;

    public function setCode(string $code): RegionInterface;

    public function getName(): ?string;

    public function setName(string $name): RegionInterface;

    public function getStatus(): int;

    public function setStatus(int $status): RegionInterface;

    public function getSortOrder(): int;

    public function setSortOrder(int $sortOrder): RegionInterface;
}
