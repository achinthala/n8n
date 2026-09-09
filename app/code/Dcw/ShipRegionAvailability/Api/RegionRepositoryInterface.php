<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Api;

use Dcw\ShipRegionAvailability\Api\Data\RegionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface RegionRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(RegionInterface $region): RegionInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $regionId): RegionInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code): RegionInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(RegionInterface $region): bool;

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $regionId): bool;

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
