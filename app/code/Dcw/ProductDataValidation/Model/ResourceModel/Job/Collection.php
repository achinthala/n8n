<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model\ResourceModel\Job;

use Dcw\ProductDataValidation\Model\Job;
use Dcw\ProductDataValidation\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'job_id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(Job::class, JobResource::class);
    }
}
