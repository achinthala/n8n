<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Dcw\CustomCompany\Model\Customer;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;

/**
 * Check if the customer is a superuser
 */
class IsSuperUser
{
    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly CompanyRepositoryInterface $companyRepository
    ) {
    }

    /**
     * Check if customer is superuser for any company except the excluded companies
     *
     * @param CustomerInterface $customer
     * @param int[] $excludeCompanyIds
     * @return bool
     * @throws LocalizedException
     */
    public function execute(CustomerInterface $customer, array $excludeCompanyIds = []): bool
    {
        $this->searchCriteriaBuilder->addFilter(CompanyInterface::SUPER_USER_ID, $customer->getId());
        if (!empty($excludeCompanyIds)) {
            $this->searchCriteriaBuilder->addFilter(CompanyInterface::COMPANY_ID, $excludeCompanyIds, 'nin');
        }
        return (bool) $this->companyRepository->getList($this->searchCriteriaBuilder->create())->getTotalCount();
    }
}
