<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model;

use Dcw\ShipRegionAvailability\Api\Data\RegionInterface;
use Dcw\ShipRegionAvailability\Api\RegionRepositoryInterface;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Region as RegionResource;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class RegionRepository implements RegionRepositoryInterface
{
    public function __construct(
        private readonly RegionResource $resource,
        private readonly RegionFactory $regionFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(RegionInterface $region): RegionInterface
    {
        try {
            $this->resource->save($region);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save region: %1', $exception->getMessage()), $exception);
        }
        return $region;
    }

    public function getById(int $regionId): RegionInterface
    {
        $region = $this->regionFactory->create();
        $this->resource->load($region, $regionId);
        if (!$region->getRegionId()) {
            throw new NoSuchEntityException(__('Region with id "%1" does not exist.', $regionId));
        }
        return $region;
    }

    public function getByCode(string $code): RegionInterface
    {
        $region = $this->regionFactory->create();
        $this->resource->load($region, $code, RegionInterface::CODE);
        if (!$region->getRegionId()) {
            throw new NoSuchEntityException(__('Region with code "%1" does not exist.', $code));
        }
        return $region;
    }

    public function delete(RegionInterface $region): bool
    {
        try {
            $this->resource->delete($region);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete region: %1', $exception->getMessage()), $exception);
        }
        return true;
    }

    public function deleteById(int $regionId): bool
    {
        return $this->delete($this->getById($regionId));
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
}
