<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Api;

use Dcw\ShipRegionAvailability\Api\Data\ZipInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ZipRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(ZipInterface $zip): ZipInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $zipId): ZipInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(ZipInterface $zip): bool;

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $zipId): bool;

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
