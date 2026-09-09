<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\ResourceModel\Region;

use Dcw\ShipRegionAvailability\Model\Region;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Region as RegionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'region_id';

    protected function _construct(): void
    {
        $this->_init(Region::class, RegionResource::class);
    }
}
