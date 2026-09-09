<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Zip extends AbstractDb
{
    public const TABLE_NAME = 'dcw_ship_region_zip';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'zip_id');
    }
}
