<?php

namespace Narvar\Accord\Helper;

use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Config\MagentoConfig as MagentoConfig;
use Narvar\Accord\Helper\NarvarClient;
use Narvar\Accord\Config\Config;
use Magento\Store\Model\StoreManagerInterface;
use Narvar\Accord\Logger\NarvarAccordLogger;

class CustomLogger
{
    protected $logger;

    private $constants;

    private $magentoConfig;

    private $client;

    private $config;

    private $storeManager;

    public function __construct(
        NarvarAccordLogger $logger,
        Constants $constants,
        MagentoConfig $magentoConfig,
        NarvarClient $client,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->logger        = $logger;
        $this->constants     = $constants->getConstants();
        $this->magentoConfig = $magentoConfig;
        $this->client        = $client;
        $this->config        = $config;
        $this->storeManager = $storeManager;
    }


    public function info($data)
    {
        $this->logger->info($data);
    }


    public function debug($data, $storeId, $forceDebug = false)
    {
        try {
            if ($this->isDebugEnabled($storeId) || $forceDebug) {
                $this->logger->debug($data);
                $configData = $this->config->getConfigByStoreId($storeId);
                $debugApi = $configData[$this->constants['LOGGING_HOST']] .
                    $configData[$this->constants['LOGGING_DEBUG_API']];
                $this->logger->info('Sending Debug Data to Narvar for Store Id ' . $storeId);
                return $this->sendLog($debugApi, $data, $storeId);
            }
        } catch (\Exception $ex) {
            $this->logger->error("Error Sending Debug Data to Narvar : " . $ex->getMessage());
        }
    }


    public function error($data, $storeId = '', $ex = [])
    {
        $this->logger->error($data, $ex);
        try {
            if (empty($storeId)) {
                $storeId = $this->storeManager->getStore()->getId();
                $storeIdMessage = 'StoreId : ' . $storeId . ' fetched using Extension ';
                $this->logger->info($storeIdMessage);
                $data = $storeIdMessage . PHP_EOL . ' Error Data : ' . $data;
            }
            $configData = $this->config->getConfigByStoreId($storeId);
            $errorApi = $configData[$this->constants['LOGGING_HOST']] .
                $configData[$this->constants['LOGGING_ERROR_API']];
            $this->logger->info('Sending Error Data to Narvar for Store Id ' . $storeId);
            return $this->sendLog($errorApi, $data, $storeId);
        } catch (\Exception $ex) {
            $this->logger->error("Error Sending Error Data to Narvar : " . $ex->getMessage());
        }
    }

    private function isDebugEnabled($storeId)
    {
        return $this->magentoConfig->get(
            $this->constants['DEBUG_MODE'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
    }

    private function sendLog($api, $data, $storeId)
    {
        $headers  = $this->getHeaders($data, $storeId);
        $method   = 'POST';
        return $this->client->send($api, $method, $data, $headers);
    }

    private function getHeaders($data, $storeId)
    {
        $headers = [];
        $retailerMoniker = $this->getRetailerMoniker($storeId);
        $authKey         = $this->getAuthKey($storeId);
        if (!empty($retailerMoniker)) {
            $headers[$this->constants['LOGGING_HEADER_RETAILER']] = $retailerMoniker;
        }
        if (!empty($authKey)) {
            $headers[$this->constants['HMAC_HEADER']]  = base64_encode(hash_hmac("sha256", $data, $authKey, true));
        }
        $headers[$this->constants['LOGGING_HEADER_STORE']] = $storeId;
        $headers[$this->constants['LOGGING_HEADER_PLATFORM']] = $this->constants['LOGGING_PLATFORM_NAME'];
        $headers['Content-Type'] = 'text/plain';
        return $headers;
    }

    private function getAuthKey($storeId)
    {
        return $this->magentoConfig->get(
            $this->constants['AUTH_KEY'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
    }

    private function getRetailerMoniker($storeId)
    {
        return $this->magentoConfig->get(
            $this->constants['RETAILER_MONIKER'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
    }
}
