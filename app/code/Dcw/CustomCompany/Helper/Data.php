<?php
namespace Dcw\CustomCompany\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Dcw\CustomCompany\Model\Source\FlooringJobs
     */
    protected $flooringJobs;
    
    /**
     * @var \Dcw\CustomCompany\Model\Source\BusinessOptions
     */
    protected $businessOptions;
    
    /**
     * @param \Dcw\CustomCompany\Model\Source\BusinessOptions $businessOptions
     * @param \Dcw\CustomCompany\Model\Source\FlooringJobs $flooringJobs
     */
    protected $context;
    
    public function __construct(
        \Dcw\CustomCompany\Model\Source\BusinessOptions $businessOptions,
        \Dcw\CustomCompany\Model\Source\FlooringJobs $flooringJobs,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->businessOptions = $businessOptions;
        $this->flooringJobs = $flooringJobs;
        parent::__construct($context);
    }
    
     /**
      * Get Role Data.
      *
      * @return DataObject[]
      */
    public function businessoption()
    {
        return $this->businessOptions->toOptionArray();
    }
    
     /**
      * Get Role Data.
      *
      * @return DataObject[]
      */
    public function flooringJobsoption()
    {
         
        return $this->flooringJobs->toOptionArray();
    }

    public function getConfig($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scope, $store);
    }
}
