<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Enquiry extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('dcw_product_enquiry', 'enquiry_id');
    }
}
