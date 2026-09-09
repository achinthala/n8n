<?php

/**
 * Copyright © Narvar, Inc. All rights reserved.
 */

 namespace Narvar\Accord\Api;

/**
 * Narvar Order Management interface.
 *
 * For Narvar Order Syncing
 * @api
 */
interface NarvarOrderManagementInterface
{
    /**
     * Lists orders that match specified search criteria.
     *
     * This call returns an array of objects, but detailed information about each object’s attributes might not be
     * included.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     * @return mixed[]
     */
    public function getOrders(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
