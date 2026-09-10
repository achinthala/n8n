<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Validation result entity (one product/store failure row).
 */
class Result extends AbstractModel
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Result::class);
    }

    public function getResultId(): ?int
    {
        $id = $this->getData('result_id');

        return $id !== null ? (int) $id : null;
    }
}
