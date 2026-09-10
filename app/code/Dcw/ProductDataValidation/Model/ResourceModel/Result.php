<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Result extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init('dcw_product_data_validation_result', 'result_id');
    }
}
