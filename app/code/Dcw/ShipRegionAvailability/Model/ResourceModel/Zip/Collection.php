<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\ResourceModel\Zip;

use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip as ZipResource;
use Dcw\ShipRegionAvailability\Model\Zip;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'zip_id';

    protected function _construct(): void
    {
        $this->_init(Zip::class, ZipResource::class);
    }
}
