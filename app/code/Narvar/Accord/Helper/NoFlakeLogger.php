<?php

namespace Narvar\Accord\Helper;

use Narvar\Accord\Helper\Sender;
use Narvar\Accord\Config\Config;
use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\AccordException;
use Narvar\Accord\Helper\Util;

class NoFlakeLogger
{
    private $sender;

    private $constants;

    private $config;

    private $logger;

    private $util;

    /**
     * Constructor
     *
     * @param Sender          $sender        to send data to narvar
     * @param Config          $config        narvar extension config
     * @param Constants       $constants     Narvar\Accord\Helper\Constants\Constants;
     * @param CustomLogger    $logger        Custom logger
     * @param Util            $util          Util
     */
    public function __construct(
        Sender $sender,
        Constants $constants,
        Config $config,
        CustomLogger $logger,
        Util $util
    ) {
        $this->sender        = $sender;
        $this->config        = $config;
        $this->constants     = $constants->getConstants();
        $this->logger        = $logger;
        $this->util          = $util;
    }

    /**
     * Method to push order data data to no flake
     *
     * @param $data               Raw payload from magento
     *
     * @return object
     */
    public function logNoFlakeData($data, $eventName, $retailerMoniker)
    {
        try {
            $configData = $this->config->getConfigByStoreId($data['store_id']);
            $api = $configData[$this->constants['NO_FLAKE_HOST']] . $configData[$this->constants['NO_FLAKE_API']];
            $noFlakeData = $this->generateNoFlakeSchema(
                $data,
                $eventName,
                $retailerMoniker
            );
            $body = $this->util->encode_object($noFlakeData);
            return $this->sender->post($api, $noFlakeData, $body, []);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() . ' in No Flake Logger';
            $errorLog = [];
            $errorLog['data'] = $data;
            $errorLog['eventName'] = $eventName;
            $errorLog['retailerMoniker'] = $retailerMoniker;
            $errorLog['message'] = $errorMessage;
            $this->logger->error(json_encode($errorLog), $data['store_id']);
            throw new AccordException($errorMessage);
        }
    }

    public function generateNoFlakeSchema($data, $eventName, $retailerMoniker)
    {
        $date = date($this->constants['NO_FLAKE_DATE_FORMAT']);

        $noFlakeObject = [];
        $noFlakeObject['tag'] = $this->constants['NO_FLAKE_TAG'];
        $noFlakeObject['retailer_moniker'] = $retailerMoniker;
        $noFlakeObject['source'] = $this->constants['NO_FLAKE_SOURCE'];
        $noFlakeObject['store_id'] = $data['store_id'];
        $noFlakeObject['event_name'] = $eventName;
        $noFlakeObject['order_id'] = $data['increment_id'];

        $this->logger->info(json_encode($noFlakeObject, JSON_UNESCAPED_SLASHES));
        
        $noFlakeObject['event_ts'] = $date;
        $noFlakeObject['ingestion_timestamp'] = $date;
        $noFlakeObject['json'] = $data;
        return $noFlakeObject;
    }
}
