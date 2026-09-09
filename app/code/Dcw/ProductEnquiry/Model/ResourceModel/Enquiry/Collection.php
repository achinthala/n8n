<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Model\ResourceModel\Enquiry;

use Dcw\ProductEnquiry\Model\Enquiry;
use Dcw\ProductEnquiry\Model\ResourceModel\Enquiry as EnquiryResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'enquiry_id';

    protected function _construct(): void
    {
        $this->_init(Enquiry::class, EnquiryResource::class);
    }
}
