<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model\ResourceModel\Result;

use Dcw\ProductDataValidation\Model\Result;
use Dcw\ProductDataValidation\Model\ResourceModel\Result as ResultResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'result_id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(Result::class, ResultResource::class);
    }
}
