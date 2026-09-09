<?php
namespace Dcw\CustomCompany\Model\Company;

class DataProvider extends \Magento\Company\Model\Company\DataProvider
{
    
    /**
     * Return Company Data.
     *
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return Array[]
     */
    public function getGeneralData(\Magento\Company\Api\Data\CompanyInterface $company)
    {
        
        $result = parent::getGeneralData($company);
        # add custom column value to the General data section
        #so that the value populates the custom field on the admin company edit page
        $result['flooring_jobs_per_year'] = $company->getFlooringJobsPerYear();
        $result['business_focus'] = $company->getBusinessFocus();
        $result['contact_by_phone'] = $company->getContactByPhone();
        $result['loading_dock'] = $company->getLoadingDock();
        $result['company_website'] = $company->getCompanyWebsite();
        $result['business_focus_others'] = $company->getBusinessFocusOthers();
        $result['company_id'] = $company->getCompanyId();
        $result['toll_free_number'] = $company->getTollFreeNumber();
        $result['sales_rep_name'] = $company->getSalesRepName();
        $result['sales_rep_email'] = $company->getSalesRepEmail();
        $result['total_order_value'] = $company->getTotalOrderValue();
        $result['unlock_next_level'] = $company->getUnlockNextLevel();
        $result['job_title_other'] = $company->getJobTitleOther();
        return $result;
    }
}
