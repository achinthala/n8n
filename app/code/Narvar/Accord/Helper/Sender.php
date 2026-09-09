<?php

namespace Narvar\Accord\Helper;

use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\NarvarClient;
use Narvar\Accord\Config\MagentoConfig;
use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Helper\Util;
use Narvar\Accord\Helper\AccordException;

define('ERROR_MESSAGE', 'Error sending data');
define('SUCCESS_MESSAGE', 'Data successfully sent');
define('VALIDATION_ERROR', 'Invalid input');

class Sender
{

    private $logger;

    private $client;

    private $magentoConfig;

    private $constants;

    private $util;

    /**
     * Constructor
     *
     * @param CustomLogger  $logger        Custom logger
     * @param NarvarClient  $client        Narvar http client.
     * @param magentoConfig $magentoConfig Auth helper to get the narvar auth keys from magento config.
     * @param Constants     $constants     Narvar\Accord\Helper\Constants\Constants;
     */
    public function __construct(
        CustomLogger $logger,
        NarvarClient $client,
        MagentoConfig $magentoConfig,
        Constants $constants,
        Util $util
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->magentoConfig = $magentoConfig;
        $this->constants     = $constants->getConstants();
        $this->util          = $util;
    }


    public function post($api, $data, $body, $headers = [])
    {
        return $this->send($api, $data, $body, 'POST', $headers);
    }

    public function put($api, $data, $body, $headers = [])
    {
        return $this->send($api, $data, $body, 'PUT', $headers);
    }
    /**
     * Method to push data to narvar
     *
     * @param $api     destination rest api
     * @param $body    string
     * @param $headers Array of http header
     *
     * @return object
     */
    private function send($api, $data, $body, $method, $headers = [])
    {
        $this->isValidRequest($data, $api, $headers);
        try {
            $headers['Content-Type'] = 'application/json';
            $response = $this->client->send($api, $method, $body, $headers);

            $loggingMetadata = $this->getLoggingMetadata($data);
            $responseObj = $this->makeResponseObject($response, $body, $loggingMetadata);

            $this->util->logMetadata(
                $loggingMetadata['order_id'],
                $loggingMetadata['store_id'],
                $loggingMetadata['event_name'],
                'end'
            );
            return $responseObj;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() . ' in Sender ' . __METHOD__;
            $this->logError($data, $api, $errorMessage, $headers);
            throw new AccordException($errorMessage);
        }
    }

    private function isValidRequest($data, $api, $headers)
    {
        if (!is_array($data) || !$api) {
            $errorMessage = $this->constants['INVALIDARGUMENTEXCEPTION'] . ' in Sender '
            . __METHOD__;
            $this->logError($data, $api, $errorMessage, $headers);
            throw new AccordException($errorMessage);
        }
    }

    private function makeResponseObject($response, $body, $loggingMetadata)
    {
        $responseObj = [];
        $responseObj['response_code'] = $response->getStatusCode();
        $responseObj['content']       = $response->getContent();
        
        $responseLog = $loggingMetadata;
        $responseLog['request'] = $body;

        if ($response->isSuccess() === true) {
            $responseObj['status']   = SUCCESS_MESSAGE;
            $responseObj['error']    = false;

            $responseLog['response'] = $responseObj;
            $this->logger->debug(json_encode($responseLog), $loggingMetadata['store_id']);
        } else {
            $responseObj['status']   = ERROR_MESSAGE;
            $responseObj['error']    = true;
           
            $responseLog['response'] = $responseObj;
            $this->logger->error(json_encode($responseLog), $loggingMetadata['store_id']);
        }
        return $responseObj;
    }


    public function logError($data, $api, $errorMessage, $headers)
    {
        $errorLog = [];
        $errorLog['api'] = $api;
        $errorLog['data'] = $data;
        $errorLog['headers'] = $headers;
        $errorLog['message'] = $errorMessage;
        $this->logger->error(json_encode($errorLog));
    }

    public function getLoggingMetadata($obj)
    {
        $loggingMetadata = [];
        
        $loggingMetadata['event_name'] = '';
        $loggingMetadata['store_id'] = '';
        $loggingMetadata['order_id'] = '';

        if (array_key_exists('event_name', $obj)) {
            $loggingMetadata['event_name'] = $obj['event_name'];
        }
        if (array_key_exists('increment_id', $obj)) {
            $loggingMetadata['order_id'] = $obj['increment_id'];
        }
        if (array_key_exists('store_id', $obj)) {
            $loggingMetadata['store_id'] = $obj['store_id'];
        }
        return $loggingMetadata;
    }
}
