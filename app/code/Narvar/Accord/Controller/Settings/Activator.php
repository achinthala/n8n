<?php

namespace Narvar\Accord\Controller\Settings;

use Narvar\Accord\Helper\Processor;
use Narvar\Accord\Config\Config as MyConfig;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Framework\App\ProductMetadataInterface as ProductMetadata;
use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Config\MagentoConfig as MagentoConfig;
use Magento\Framework\Module\ModuleListInterface as ModuleList;
use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\AccordException;
use Magento\Framework\Exception\ValidatorException as ValidationException;

class Activator
{

    private $processor;

    private $myConfig;

    private $storeManager;

    private $magentoConfig;

    private $productMetadata;

    private $moduleList;

    private $constants;

    private $logger;

    private const STORE_KEY     = 'store';
    private const WEBSITE_KEY   = 'website';
    private const BASE_URL_KEY  = 'base_url';

    /**
     * Constructor
     *
     * @param Processor       $processor       Narvar\Accord\Helper\Processor
     * @param MyConfig        $myConfig        Narvar\Accord\Config\Config
     * @param StoreManager    $storeManager    Magento\Store\Model\StoreManagerInterface
     * @param MagentoConfig   $magentoConfig   Narvar\Accord\Config\MagentoConfig
     * @param ProductMetadata $productMetadata Magento\Framework\App\ProductMetadataInterface
     * @param ModuleList      $moduleList      Magento\Framework\Module\ModuleListInterface
     * @param Constants       $constants       Narvar\Accord\Helper\Constants\Constants
     * @param CustomLogger    $logger          Custom logger
     */
    public function __construct(
        Processor $processor,
        MyConfig $myConfig,
        StoreManager $storeManager,
        MagentoConfig $magentoConfig,
        ProductMetadata $productMetadata,
        ModuleList $moduleList,
        Constants $constants,
        CustomLogger $logger
    ) {
        $this->processor       = $processor;
        $this->myConfig        = $myConfig;
        $this->storeManager    = $storeManager;
        $this->magentoConfig   = $magentoConfig;
        $this->productMetadata = $productMetadata;
        $this->moduleList      = $moduleList;
        $this->constants       = $constants->getConstants();
        $this->logger          = $logger;
    }


    /**
     * Method to send reatiler moniker, auth tiken and metadata to narvar.
     *
     * @param $config magento config.
     *
     * @return void
     */
    public function beforeSave(\Magento\Config\Model\Config $config)
    {
        $responseObj = [];
        try {
            $groupConfig           = $config->getGroups();
            if (array_key_exists('narvar_settings', $groupConfig)) {
                $magentoSettingsFields = $groupConfig['narvar_settings']['fields'];
                $storeInfo = [];
                $narvarAccordEnabled = false;
                if ($config->getStore()) {
                    $storeId        = $config->getStore();
                    $narvarAccordEnabled = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['ACCORD_ENABLED'],
                        $this->constants['STORE_SCOPE'],
                        $storeId
                    );

                    $store          = $this->storeManager->getStore($storeId);
                    $storeData      = $store->getData();
                    $storeWebsiteId = $store->getWebsiteId();
                    $storeBaseUrl   = $store->getBaseUrl();
                    $websiteData    = $this->storeManager->getWebsite($storeWebsiteId)->getData();
                    $narvarRetailerMoniker = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['RETAILER_MONIKER'],
                        $this->constants['STORE_SCOPE'],
                        $storeId
                    );
                    $narvarAuth            = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['AUTH_KEY'],
                        $this->constants['STORE_SCOPE'],
                        $storeId
                    );
                    $isProduction = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['PRODUCTION_ENVIRONMENT'],
                        $this->constants['STORE_SCOPE'],
                        $storeId
                    );
                    $narvarIntegrationToken = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['INTEGRATION_TOKEN'],
                        $this->constants['STORE_SCOPE'],
                        $storeId
                    );
                    $narvarIntegrationTokenSecret = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['INTEGRATION_TOKEN_SECRET'],
                        $this->constants['STORE_SCOPE'],
                        $storeId
                    );
                    $narvarStoreBaseUrl = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['STORE_BASE_URL'],
                        $this->constants['STORE_SCOPE'],
                        $storeId
                    );
                    $storeInfo[]           = [
                        self::STORE_KEY    => $storeData,
                        self::WEBSITE_KEY  => $websiteData,
                        self::BASE_URL_KEY => $storeBaseUrl,
                    ];
                } else {
                    $narvarAccordEnabled = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['ACCORD_ENABLED'],
                        $this->constants['WEBSITE_SCOPE']
                    );
                  
                    if ($config->getWebsite()) {
                        $webSiteObj = $this->storeManager->getWebsite($config->getWebsite());
                        $stores     = $webSiteObj->getStores();
                    } else {
                        $stores = $this->storeManager->getStores();
                    }
                    $narvarRetailerMoniker = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['RETAILER_MONIKER'],
                        $this->constants['WEBSITE_SCOPE']
                    );
                    $isProduction = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['PRODUCTION_ENVIRONMENT'],
                        $this->constants['WEBSITE_SCOPE']
                    );
                    $narvarAuth            = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['AUTH_KEY'],
                        $this->constants['WEBSITE_SCOPE']
                    );
                    $narvarIntegrationToken = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['INTEGRATION_TOKEN'],
                        $this->constants['WEBSITE_SCOPE']
                    );
                    $narvarIntegrationTokenSecret = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['INTEGRATION_TOKEN_SECRET'],
                        $this->constants['WEBSITE_SCOPE']
                    );
                    $narvarStoreBaseUrl = $this->getNarvarSettings(
                        $magentoSettingsFields,
                        $this->constants['STORE_BASE_URL'],
                        $this->constants['WEBSITE_SCOPE']
                    );
                    foreach ($stores as $store) {
                        $storeInfo[] = [
                            self::STORE_KEY    => $store->getData(),
                            self::WEBSITE_KEY  => $this->storeManager->getWebsite($store->getWebsiteId())->getData(),
                            self::BASE_URL_KEY => $store->getBaseUrl(),
                        ];
                    }
                }
                if ($narvarAccordEnabled) {
                    $authObject = [
                    "retailer_moniker"      => $narvarRetailerMoniker,
                    "narvar_integration_token"  => $narvarIntegrationToken,
                    "narvar_integration_token_secret" => $narvarIntegrationTokenSecret,
                    "narvar_store_base_url" => $narvarStoreBaseUrl,
                    "base_url_secure"       => $this->magentoConfig->
                        getConfigValue('web/secure/base_url', null, $this->constants['GLOBAL_SCOPE']),
                    "base_url_unsecure"       => $this->magentoConfig->
                        getConfigValue('web/unsecure/base_url', null, $this->constants['GLOBAL_SCOPE']),
                    "magento_version"       => sprintf(
                        '%s-%s',
                        $this->productMetadata->getVersion(),
                        $this->productMetadata->getEdition()
                    ),
                    "narvar_accord_version" => '1.1.3',
                    "store_info"            => $storeInfo,
                    ];
                    $logData    = [];
                    $logData['auth_object'] = $authObject;
                    $logData['entity_type'] = 'auth';
                    $logData['milestone']   = 'start';
                    $this->logger->info(json_encode($logData));
                    $responseObj = $this->processor->sendAuthData(
                        $authObject,
                        $narvarAuth,
                        $narvarRetailerMoniker,
                        $isProduction
                    );
                    if (!array_key_exists('response_code', $responseObj) || $responseObj["response_code"] != 200) {
                        $this->logger->error('Failure Auth handshake response : ' . json_encode($responseObj));
                        throw new \Exception($this->constants['INVALIDCREDENTIALSEXCEPTION']);
                    }
                }
            }
        } catch (\Exception $ex) {
              $errorMessage = 'Caught exception :' . $ex->getMessage() . ' in Activator ' . __METHOD__;
              $this->logger->error($errorMessage);
              throw new AccordException('Error occured while saving configuration');
        }
    }


    /**
     * Method to get config from magento based on the key passed in.
     *
     * @param $settings     magento settings.
     * @param $key          config we want to find
     * @param $currentScope current scope eg Store, website or default.
     * @param $storeId      store id
     *
     * @return string
     */
    public function getNarvarSettings($settings, $key, $currentScope, $storeId = null)
    {
        if (!array_key_exists($key, $settings)) {
            $logData    = [];
            $logData['error'] = "invalid key in settings";
            $logData['settings'] = json_encode($settings);
            $logData['key']   = $key;
            $logData['store_id']   = $storeId;
            $this->logger->error(json_encode($logData));
            return null;
        }
        if (array_key_exists('inherit', $settings[$key])) {
            /* Inherit the retailer moniker from website scope
            if in store scope and from global scope if in website scope. */
            $value = $this->magentoConfig->get($key, $this->inheritFrom($currentScope), $storeId);
        } else {
            $value = $settings[$key]['value'];
        }

        return $value;
    }


    /**
     * Method to get from the parent scope for a given scope
     *
     * @param $currentScope current magento scope.
     *
     * @return string
     */
    private function inheritFrom($currentScope)
    {
        if ($currentScope === $this->constants['STORE_SCOPE']) {
            return $this->constants['WEBSITE_SCOPE'];
        } elseif ($currentScope === $this->constants['WEBSITE_SCOPE']) {
            return $this->constants['GLOBAL_SCOPE'];
        } else {
            return $this->constants['GLOBAL_SCOPE'];
        }
    }
}
