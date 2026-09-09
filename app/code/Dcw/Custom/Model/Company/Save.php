<?php
namespace Dcw\Custom\Model\Company;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveHandlerPool;
use Magento\Company\Model\ResourceModel\Company;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\SaveValidatorPool;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class responsible for creating and updating company entities.
 */
class Save extends \Magento\Company\Model\Company\Save
{
    /**
     * @var SaveHandlerPool
     */
    private $saveHandlerPool;

    /**
     * @var Company
     */
    private $companyResource;

    /**
     * @var CompanyInterfaceFactory
     */
    private $companyFactory;

    /**
     * @var SaveValidatorPool
     */
    private $saveValidatorPool;

    /**
     * @var CollectionFactory
     */
    private $userCollectionFactory;

    /**
     * @param SaveHandlerPool $saveHandlerPool
     * @param Company $companyResource
     * @param CompanyInterfaceFactory $companyFactory
     * @param SaveValidatorPool $saveValidatorPool
     * @param CollectionFactory $userCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SaveHandlerPool $saveHandlerPool,
        Company $companyResource,
        CompanyInterfaceFactory $companyFactory,
        SaveValidatorPool $saveValidatorPool,
        CollectionFactory $userCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->saveHandlerPool = $saveHandlerPool;
        $this->companyResource = $companyResource;
        $this->companyFactory = $companyFactory;
        $this->saveValidatorPool = $saveValidatorPool;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Save the company data.
     *
     * @param CompanyInterface $company
     * @return CompanyInterface
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function save(CompanyInterface $company)
    { 
        $this->processAddress($company);
        $this->processSalesRepresentative($company);
        $companyId = $company->getId();
        $initialCompany = $this->getInitialCompany($companyId);
        $this->saveValidatorPool->execute($company, $initialCompany);
        
        try {
            $this->companyResource->save($company);
            $this->saveHandlerPool->execute($company, $initialCompany);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save company'),
                $e
            );
        }

        return $company;
    }

    /**
     * Get initial company.
     *
     * @param int|null $companyId
     * @return CompanyInterface
     */
    private function getInitialCompany($companyId)
    {
        $company = $this->companyFactory->create();
        try {
            $this->companyResource->load($company, $companyId);
        } catch (\Exception $e) {
            // Do nothing, just leave the object blank.
        }

        return $company;
    }

    /**
     * Process sales representative for the company.
     *
     * @param CompanyInterface $company
     * @return void
     */
    private function processSalesRepresentative(CompanyInterface $company)
    {
        if (!$company->getSalesRepresentativeId()) {
            $adminuserId = $this->scopeConfig->getValue('dcw_sales_rep/sales_rep/admin_user_dropdown');
            if(!$adminuserId){
            $userCollection = $this->userCollectionFactory->create();
            $company->setSalesRepresentativeId($userCollection->setPageSize(1)->getFirstItem()->getId());
            } else 
            {            
            $company->setSalesRepresentativeId($adminuserId);
            }
            
        }
    }

    /**
     * Process company address.
     *
     * @param CompanyInterface $company
     * @return void
     */
    private function processAddress(CompanyInterface $company)
    {
        if (!$company->getRegionId()) {
            $company->setRegionId(null);
        } else {
            $company->setRegion(null);
        }

        $street = $company->getStreet();
        if (is_array($street) && count($street)) {
            $company->setStreet(trim(implode("\n", $street)));
        }
    }
}
