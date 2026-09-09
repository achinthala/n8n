<?php

namespace Narvar\Accord\Config;

use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Config\MagentoConfig;

class Config
{

    private $defaultConfig;

    private $prodConfig;

    private $constants;

    private $magentoConfig;

    /**
     * Constructor
     */
    public function __construct(
        Constants $constants,
        MagentoConfig $magentoConfig
    ) {
        $this->constants     = $constants->getConstants();
        $configFile          = file_get_contents(__DIR__ . '/Default.json');
        $this->defaultConfig = json_decode($configFile, true);
        $configFile          = file_get_contents(__DIR__ . '/Production.json');
        $this->prodConfig    = json_decode($configFile, true);
        $this->magentoConfig = $magentoConfig;
    }

    /**
     * Method to get narvar extension config by storeId
     *
     * @return object
     */
    public function getConfigByStoreId($storeId)
    {
        $isProduction = $this->isProductionEnvironment($storeId);
        return $this->getConfigByIsProduction($isProduction);
    }

    /**
     * Method to get narvar extension config by isProduction
     *
     * @return object
     */
    public function getConfigByIsProduction($isProduction)
    {
        if ($isProduction) {
            return $this->prodConfig;
        } else {
            return $this->defaultConfig;
        }
    }

    private function isProductionEnvironment($storeId)
    {
        return $this->magentoConfig->get(
            $this->constants['PRODUCTION_ENVIRONMENT'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
    }
}
