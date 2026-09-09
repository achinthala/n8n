<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ShareCart extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('dcw_share_cart', 'share_id');
    }
}
