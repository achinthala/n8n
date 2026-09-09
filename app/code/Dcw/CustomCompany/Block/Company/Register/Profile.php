<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\CustomCompany\Block\Company\Register;

use Magento\Framework\DataObject;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;

class Profile extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Company\Api\CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var \Magento\Company\Model\CountryInformationProvider
     */
    private $countryInformationProvider;

    /**
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $customerMetadata;

    /**
     * @var \Magento\Company\Model\Create\Session
     */
    private $companyCreateSession;

    /**
     * @var \Magento\Company\Api\Data\CompanyInterface
     */
    private $company;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    private $companyAdmin;
    
    /**
     * @var \Dcw\CustomCompany\Model\Source\BusinessOptions
     */
    private $businessOption;
    
    /**
     * @var \Dcw\CustomCompany\Model\Source\FlooringJobs
     */
    private $flooringJobs;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Company\Api\CompanyManagementInterface $companyManagement
     * @param \Magento\Company\Model\CountryInformationProvider $countryInformationProvider
     * @param \Magento\Customer\Api\CustomerMetadataInterface $customerMetadata
     * @param \Magento\Company\Model\Create\Session $companyCreateSession
     * @param \Dcw\CustomCompany\Model\Source\BusinessOptions $businessOption
     * @param \Dcw\CustomCompany\Model\Source\FlooringJobs $flooringJobs
     * @param array $data [optional]
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        \Magento\Company\Model\CountryInformationProvider $countryInformationProvider,
        \Magento\Customer\Api\CustomerMetadataInterface $customerMetadata,
        \Magento\Company\Model\Create\Session $companyCreateSession,
        \Dcw\CustomCompany\Model\Source\BusinessOptions $businessOption,
        \Dcw\CustomCompany\Model\Source\FlooringJobs $flooringJobs,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->companyManagement = $companyManagement;
        $this->countryInformationProvider = $countryInformationProvider;
        $this->customerMetadata = $customerMetadata;
        $this->companyCreateSession = $companyCreateSession;
        $this->businessOption = $businessOption;
        $this->flooringJobs = $flooringJobs;
    }

    /**
     * Filter empty fields.
     *
     * @param DataObject[] $fields
     * @return DataObject[]
     */
    public function filterEmptyFields(array $fields)
    {
        return array_filter($fields, function ($field) {
            return !empty($field->getValue());
        });
    }

    /**
     * Get company information.
     *
     * @return DataObject[]
     */
    public function getCompanyInformation()
    {
        $company = $this->getCompany();
        $optionvalues=$this->businessOption->optionvalueinkey();
        $flooringoptionvalues=$this->flooringJobs->optionvalueinkey();
        $companyInformation = [
            $this->createField(
                __('Company Name:'),
                $company->getCompanyName()
            ),
            $this->createField(
                __('Legal Name:'),
                $company->getLegalName()
            ),
            $this->createField(
                __('Business Focus:'),
                $optionvalues[$company->getBusinessFocus()]
            ),
            $this->createField(
                __('Phone Number:'),
                $company->getTelephone()
            ),
            
            $this->createField(
                __('Company Website:'),
                $company->getCompanyWebsite()
            ),
            $this->createField(
                __('Flooring Jobs per year:'),
                $flooringoptionvalues[$company->getFlooringJobsPerYear()]
            ),
            
            $this->createField(
                __('VAT/TAX ID:'),
                $company->getVatTaxId()
            ),
            $this->createField(
                __('Re-seller ID:'),
                $company->getResellerId()
            ),
        ];

        return $companyInformation;
    }
    
    /**
     * Customer Address text
     *
     * @return string
     */
    public function getAddresstext()
    {
        $company = $this->getCompany();
        $companyAdmin = $this->getCompanyAdmin();
        return $companyAdmin->getFirstname().' '.$companyAdmin->getLastname()
            .'<br>'.$this->getCompanyStreetLabel($company).'<br>'.$company->getCity()
            .'<br>'.$this->countryInformationProvider->getActualRegionName(
                $company->getCountryId(),
                $company->getRegionId(),
                $company->getRegion()
            ).'<br>'.$this->countryInformationProvider->getCountryNameByCode($company->getCountryId()).
        '<br>'.$company->getPostcode().'<br>'.$company->getTelephone();
    }

    /**
     * Get address information.
     *
     * @return DataObject[]
     */
    public function getAddressInformation()
    {
        $company = $this->getCompany();

        $addressInformation = [
            $this->createField(
                __('Street Address:'),
                $this->getCompanyStreetLabel($company)
            ),
            $this->createField(
                __('City:'),
                $company->getCity()
            ),
            $this->createField(
                __('Country:'),
                $this->countryInformationProvider->getCountryNameByCode($company->getCountryId())
            ),
            $this->createField(
                __('State/Province:'),
                $this->countryInformationProvider->getActualRegionName(
                    $company->getCountryId(),
                    $company->getRegionId(),
                    $company->getRegion()
                )
            ),
            $this->createField(
                __('ZIP/Postal Code:'),
                $company->getPostcode()
            ),
            $this->createField(
                __('Phone Number:'),
                $company->getTelephone()
            )
        ];

        return $addressInformation;
    }

    /**
     * Get admin information.
     *
     * @return DataObject[]
     */
    public function getAdminInformation()
    {
        $companyAdmin = $this->getCompanyAdmin();
        $company = $this->getCompany();
        $adminInformation = [
            $this->createField(
                __('Job Title:'),
                $this->getCustomerJobTitle($companyAdmin)
            ),
            $this->createField(
                __('Email Address:'),
                $companyAdmin->getEmail()
            ),
            $this->createField(
                __('Company Website:'),
                $company->getCompanyWebsite()
            )
        ];

        return $adminInformation;
    }

    /**
     * Create field object.
     *
     * @param string|\Magento\Framework\Phrase $label
     * @param string $value
     * @return DataObject
     */
    private function createField($label, $value)
    {
        return new DataObject([
            'label' => $label,
            'value' => $value
        ]);
    }

    /**
     * Get company.
     *
     * @return CompanyInterface
     */
    private function getCompany()
    {
        if ($this->company !== null) {
            return $this->company;
        }
        $customerId = $this->companyCreateSession->getCustomerId();
        if ($customerId) {
            $this->company = $this->companyManagement->getByCustomerId($customerId);
        }
        return $this->company;
    }

    /**
     * Get company admin.
     *
     * @return CustomerInterface
     */
    private function getCompanyAdmin()
    {
        if ($this->companyAdmin === null) {
            $company = $this->getCompany();
            $this->companyAdmin = $this->companyManagement->getAdminByCompanyId($company->getId());
        }
        return $this->companyAdmin;
    }

    /**
     * Get customer job title.
     *
     * @param CustomerInterface $customer
     * @return null|string
     */
    private function getCustomerJobTitle(CustomerInterface $customer)
    {
        $jobTitle = '';
        $extensionAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        if ($extensionAttributes) {
            $jobTitle = $extensionAttributes->getJobTitle();
        }

        return $jobTitle;
    }

    /**
     * Get company street label.
     *
     * @param CompanyInterface $company
     * @return string
     */
    private function getCompanyStreetLabel(CompanyInterface $company)
    {
        $streetLabel = '';
        $streetData = $company->getStreet();
        $streetLabel .= (!empty($streetData[0])) ? $streetData[0] : '';
        $streetLabel .= (!empty($streetData[1])) ? ' ' . $streetData[1] : '';

        return $streetLabel;
    }

    /**
     * Get customer gender.
     *
     * @param CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\OptionInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerGender(CustomerInterface $customer)
    {
        try {
            $attribute = $this->customerMetadata->getAttributeMetadata(CustomerInterface::GENDER);
            $options = $attribute->getOptions();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $options = [];
        }

        return isset($options[$customer->getGender()]) ? $options[$customer->getGender()]->getLabel() : null;
    }
}
