<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Module configuration constants and scope config access.
 */
class Config
{
    public const XML_PATH_ENABLED = 'dcw_ship_region_availability/general/enabled';

    /** Child simple products: region multiselect (managed via PIM/CSV when attribute already exists). */
    public const ATTR_SHIPS_FROM_REGION = 'incstores_pim_ships_from_region';

    /** Configurable parent: enables ZIP / region UI on PDP. */
    public const ATTR_SHIP_REGION_AVAILABILITY = 'incstores_pim_ship_region_availability';

    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
