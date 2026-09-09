<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\Config\Source;

use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class RegionIdOptions implements OptionSourceInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', Config::STATUS_ENABLED);
        $collection->setOrder('sort_order', 'ASC');
        $collection->setOrder('name', 'ASC');

        foreach ($collection as $region) {
            $options[] = [
                'value' => (int) $region->getRegionId(),
                'label' => sprintf('%s (%s)', $region->getName(), $region->getCode()),
            ];
        }

        return $options;
    }
}
