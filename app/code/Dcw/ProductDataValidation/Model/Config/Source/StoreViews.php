<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as SystemStore;

/**
 * Store view options for validation scope configuration.
 */
class StoreViews implements OptionSourceInterface
{
    public function __construct(
        private readonly SystemStore $systemStore
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return $this->systemStore->getStoreValuesForForm(false, false);
    }
}
