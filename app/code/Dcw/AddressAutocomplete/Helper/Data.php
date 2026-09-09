<?php


namespace Dcw\AddressAutocomplete\Helper;


/**
 * Shipping data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    private $storeId;
    /**
     * @var Mage_Sales_Model_Quote
     */


    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }


    /**
     * Gets a config flag
     *
     * @param $configField
     * @return mixed
     */
    public function getConfigFlag($configField)
    {
        return $this->scopeConfig->isSetFlag($configField, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Config Value
     *
     * @param $configField
     * @return mixed
     */
    public function getConfigValue($configField, $store = null)
    {
        return $this->scopeConfig->getValue(
            $configField,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

}
