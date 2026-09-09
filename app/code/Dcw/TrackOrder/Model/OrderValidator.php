<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 * Validates order existence for guest order lookup.
 */

declare(strict_types=1);

namespace Dcw\TrackOrder\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class OrderValidator
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Check if order exists by increment ID (searches across all stores)
     *
     * @param string $incrementId
     * @return bool
     */
    public function isOrderExists(string $incrementId): bool
    {
        $incrementId = trim($incrementId);
        if ($incrementId === '') {
            return false;
        }
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->create();
            $records = $this->orderRepository->getList($searchCriteria);
            return !empty($records->getItems());
        } catch (\Exception $e) {
            return false;
        }
    }
}
