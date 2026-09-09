<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Api;

use Dcw\ShipRegionAvailability\Api\Data\RegionInterface;

interface RegionLookupInterface
{
    /**
     * Resolve an active region for a US ZIP code (5-digit).
     */
    public function getRegionByZip(string $zipCode): ?RegionInterface;

    /**
     * Normalize ZIP input to 5 digits or return null when invalid.
     */
    public function normalizeZipCode(string $zipCode): ?string;
}
