<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model\ResourceModel\ShareCart;

use Dcw\ShareCart\Model\ResourceModel\ShareCart as ShareCartResource;
use Dcw\ShareCart\Model\ShareCart;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'share_id';

    protected function _construct(): void
    {
        $this->_init(ShareCart::class, ShareCartResource::class);
    }
}
