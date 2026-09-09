<?php

namespace Narvar\Accord\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Narvar\Accord\Helper\Constants\Constants;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Narvar\Accord\Helper\ScopeConfigHelper;

class MagentoConfig extends AbstractHelper
{

    public const NARVAR_SECTIONS_ID = 'narvar_accord/';
    // Narvar config settings section id.
    public const NARVAR_GROUP_ID = 'narvar_settings/';

    // Narvar config settings group id.
    private $constants;

    protected $scopeConfig;

    private $storeManager;

    private $scopeConfigHelper;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface  $scopeConfig        Magento\Framework\App\Config\ScopeConfigInterface
     * @param Constants             $constants          Narvar\Accord\Helper\Constants\Constants
     * @param StoreManagerInterface $storeManager       Narvar\Accord\Helper\Constants\Constants
     * @param ScopeConfigHelper     $scopeConfigHelper  Narvar\Accord\Helper\ScopeConfigHelper
     **/
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Constants $constants,
        StoreManagerInterface $storeManager,
        ScopeConfigHelper $scopeConfigHelper
    ) {
        $this->constants            = $constants->getConstants();
        $this->scopeConfig          = $scopeConfig;
        $this->storeManager         = $storeManager;
        $this->scopeConfigHelper    = $scopeConfigHelper;
    }


    /**
     * Method to get config value
     *
     * @param $field   config ID.
     * @param $storeId Magento Store ID.
     * @param $scope   Config scope in magento.
     *
     * @return string
     */
    public function getConfigValue($field, $scopeId, $configScope)
    {
        $scope = $this->scopeConfigHelper->getScope($configScope);
        return $this->scopeConfig->getValue(
            $field,
            $scope,
            $scopeId
        );
    }


    /**
     * Method to get settings config
     *
     * @param $code        config ID.
     * @param $configScope Magento config scope.
     * @param $storeId     Magento Store ID.
     *
     * @return string
     */
    public function get($code, $configScope, $storeId = null)
    {
        $field = self::NARVAR_SECTIONS_ID . self::NARVAR_GROUP_ID . $code;
        $val = $this->getConfigValue($field, $storeId, $configScope);
        if (empty($val) && !empty($storeId)) {
            $scopeWebsite = $this->constants['WEBSITE_SCOPE'];
            $websiteId = $this->getWebsiteIdByStoreId($storeId);
            $val = $this->getConfigValue($field, $websiteId, $scopeWebsite);
        }
        if (empty($val)) {
            $scopeGlobal = $this->constants['GLOBAL_SCOPE'];
            $val = $this->getConfigValue($field, null, $scopeGlobal);
        }
        return $val;
    }

    /**
     * Get Website Id by store id
     *
     * @param $storeId
     */
    public function getWebsiteIdByStoreId($storeId)
    {
        $websiteId = null;
        try {
            $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        } catch (\Exception $ex) {
            $websiteId = null;
        }
        return $websiteId;
    }
}
