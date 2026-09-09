<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model;

use Dcw\ShipRegionAvailability\Api\Data\ZipInterface;
use Dcw\ShipRegionAvailability\Api\ZipRepositoryInterface;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip as ZipResource;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ZipRepository implements ZipRepositoryInterface
{
    public function __construct(
        private readonly ZipResource $resource,
        private readonly ZipFactory $zipFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(ZipInterface $zip): ZipInterface
    {
        try {
            $this->resource->save($zip);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save ZIP mapping: %1', $exception->getMessage()), $exception);
        }
        return $zip;
    }

    public function getById(int $zipId): ZipInterface
    {
        $zip = $this->zipFactory->create();
        $this->resource->load($zip, $zipId);
        if (!$zip->getZipId()) {
            throw new NoSuchEntityException(__('ZIP mapping with id "%1" does not exist.', $zipId));
        }
        return $zip;
    }

    public function delete(ZipInterface $zip): bool
    {
        try {
            $this->resource->delete($zip);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete ZIP mapping: %1', $exception->getMessage()), $exception);
        }
        return true;
    }

    public function deleteById(int $zipId): bool
    {
        return $this->delete($this->getById($zipId));
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
