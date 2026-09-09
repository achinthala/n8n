<?php

namespace Dcw\CustomCompany\Block\Company;

use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\ResourceModel\Customer\CollectionFactory as CompanyUserCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Api\AclInterface;

class UserList extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    
    /**
     * @var CompanyManagementInterface
     */
    protected $companyManagement;
    
    /**
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;
    
    /**
     * @var CompanyUserCollectionFactory
     */
    protected $companyUserCollectionFactory;
    
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    
    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;
    
    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CompanyManagementInterface $companyManagement
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyUserCollectionFactory $companyUserCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param RoleRepositoryInterface $roleRepository
     * @param AclInterface $acl
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CompanyManagementInterface $companyManagement,
        CompanyRepositoryInterface $companyRepository,
        CompanyUserCollectionFactory $companyUserCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        RoleRepositoryInterface $roleRepository,
        AclInterface $acl,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->companyManagement = $companyManagement;
        $this->companyRepository = $companyRepository;
        $this->companyUserCollectionFactory = $companyUserCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->roleRepository = $roleRepository;
        $this->acl = $acl;
        parent::__construct($context, $data);
    }

    /**
     * Update group id
     *
     * @return CompanyManagementInterface $companyManagement
     */
    public function getUsers()
    {
        // Get the current customer ID
        $customerId = $this->customerSession->getCustomerId();
        // Get the current company ID
        $companyId = $this->companyManagement->getByCustomerId($customerId)->getId();
        $companyUserCollection = $this->companyUserCollectionFactory->create();
        $companyUserCollection->addFieldToFilter('company_id', $companyId);
        $companyUserCollection->addFieldToSelect('*');
        return $companyUserCollection;
    }
    
    /**
     * Load Customer
     *
     * @param string|bool|int $customerId
     * @return $customer
     */
    public function getCustomerDetailsById($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $customer = null;
        }
        return $customer;
    }
    
    /**
     * Roal get of a user
     *
     * @param string|bool|int $customerId
     * @return AclInterface $acl
     */
    public function getUserRoal($customerId)
    {
        return $roles = $this->acl->getRolesByUserId($customerId);
    }
}
