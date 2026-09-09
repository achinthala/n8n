<?php

namespace Dcw\CustomCompany\Plugin\Model;

use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;

class CompanyRepository
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    
    /**
     * @var CustomerFactory
     */
    protected $customer;
    
    /**
     * @var StoreManagerInterface
     */
    protected $storemanager;
    
    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param CustomerFactory $customer
     * @param StoreManagerInterface $storemanager
     */
    
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        CustomerFactory $customer,
        StoreManagerInterface $storemanager
    ) {
        $this->request = $request;
        $this->customer = $customer;
        $this->storemanager = $storemanager;
    }
    
    /**
     * Update Compani Attribute
     *
     * @param \Magento\Company\Model\CompanyRepository $companyRepository
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return \Magento\Company\Api\Data\CompanyInterface $company
     */
    public function afterSave(
        \Magento\Company\Model\CompanyRepository $companyRepository,
        \Magento\Company\Api\Data\CompanyInterface $company
    ) {
        $postdata=$this->request->getPostValue();

        if (isset($postdata['company']['business_focus'])) {
            $business_focus=$postdata['company']['business_focus'];
            $company->setBusinessFocus($business_focus);
            if (isset($postdata['company']['business_focus_others']) &&
            ($business_focus ==6 || $business_focus =='6')) {
                $company->setBusinessFocusOthers($postdata['company']['business_focus_others']);
            }
        }

        if (isset($postdata['company']['flooring_jobs_per_year'])) {
            $company->setFlooringJobsPerYear($postdata['company']['flooring_jobs_per_year']);
        }

        if (isset($postdata['company']['loading_dock'])) {
            $company->setLoadingDock($postdata['company']['loading_dock']);
        }

        if (isset($postdata['company']['company_website'])) {
            $company->setCompanyWebsite($postdata['company']['company_website']);
        }

        if (isset($postdata['company']['contact_by_phone'])) {
            $cont = $postdata['company']['contact_by_phone'];
            $byPhone = false;
            if ($cont == '1' || $cont == 1) {
                $byPhone=true;
            }
            $company->setContactByPhone($byPhone);
        }

        if (isset($postdata['general']['business_focus'])) {
            $business_focus=$postdata['general']['business_focus'];
            $company->setBusinessFocus($business_focus);
            if (isset($postdata['general']['business_focus_others']) &&
            ($business_focus ==6 || $business_focus =='6')) {
                $company->setBusinessFocusOthers($postdata['general']['business_focus_others']);
            }
        }
        if (isset($postdata['general']['job_title_other'])) {
            $company->setJobTitleOther($postdata['general']['job_title_other']);
        }
        if (isset($postdata['general']['contact_by_phone'])) {
            $cont=$postdata['general']['contact_by_phone'];
            $byPhone=false;
            if ($cont=='1' || $cont==1) {
                $byPhone=true;
            }
              $company->setContactByPhone($byPhone);
        }
        if (isset($postdata['general']['flooring_jobs_per_year'])) {
              $company->setFlooringJobsPerYear($postdata['general']['flooring_jobs_per_year']);
        }
        if (isset($postdata['general']['loading_dock'])) {
              $company->setLoadingDock($postdata['general']['loading_dock']);
        }
        
        if (isset($postdata['general']['company_website'])) {
              $company->setCompanyWebsite($postdata['general']['company_website']);
        }
        
        if (isset($postdata['company']['create_status'])) {
              $company->setStatus(1);
        
        }
        if (isset($postdata['general']['company_id'])) {
              $company->setCompanyId($postdata['general']['company_id']);
        }
        
        if (isset($postdata['general']['toll_free_number'])) {
              $company->setTollFreeNumber($postdata['general']['toll_free_number']);
        }
        
        if (isset($postdata['general']['sales_rep_name'])) {
              $company->setSalesRepName($postdata['general']['sales_rep_name']);
        }
        if (isset($postdata['general']['sales_rep_email'])) {
              $company->setSalesRepEmail($postdata['general']['sales_rep_email']);
        }
        
        if (isset($postdata['general']['total_order_value'])) {
              $company->setTotalOrderValue($postdata['general']['total_order_value']);
        }
        
        if (isset($postdata['general']['unlock_next_level'])) {
              $company->setUnlockNextLevel($postdata['general']['unlock_next_level']);
        }
        
        if (isset($postdata['customer']['email']) && isset($postdata['customer']['create_group_id'])) {
            $email=$postdata['customer']['email'];
            $groupId=$postdata['customer']['create_group_id'];
            $this->updateCustomerByEmail($email, $groupId);
            $company->setCustomerGroupId($groupId);
        }
         
        $company->save();
        
        return $company;
    }
    
    /**
     * Update group id
     *
     * @param string $email
     * @param string $groupId
     * @return bool
     */
    public function updateCustomerByEmail($email, $groupId)
    {
        $websiteID = $this->storemanager->getStore()->getWebsiteId();
        $customer = $this->customer->create()->setWebsiteId($websiteID)->loadByEmail($email);
        if ($customer) {
            $customer->setGroupId($groupId);
            $customer->save();
        }
 
        return true;
    }
}
