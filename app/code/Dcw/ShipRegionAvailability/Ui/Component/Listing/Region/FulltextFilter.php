<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Ui\Component\Listing\Region;

use Dcw\ShipRegionAvailability\Model\ResourceModel\Region\Grid\Collection as RegionGridCollection;
use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;

class FulltextFilter implements FilterApplierInterface
{
    public function apply(Collection $collection, Filter $filter): void
    {
        if (!$collection instanceof AbstractDb) {
            throw new \InvalidArgumentException('Database collection required.');
        }

        if (!$collection instanceof RegionGridCollection) {
            return;
        }

        $value = $filter->getValue() !== null ? trim((string) $filter->getValue()) : '';
        if ($value === '') {
            return;
        }

        $collection->addFullTextFilter($value);
    }
}
