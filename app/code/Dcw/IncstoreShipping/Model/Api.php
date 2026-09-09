<?php

declare(strict_types=1);

namespace Dcw\IncstoreShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Logger\Monolog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Api
{
    /**
     * path to config
     */
    private const API_URL = 'dcw_shipping_api/incstoreshipping_config/api_url';

    /**
     * path to config
     */
    private const API_KEY = 'dcw_shipping_api/incstoreshipping_config/api_key';

    /**
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param Monolog $logger
     */
    public function __construct(
        protected readonly Curl $curl,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Monolog $logger,
        private Client $httpClient,
    ) {
        $this->httpClient = new Client();
    }

    /**
     * @param array $params
     * @return array|false[]
     */
    public function apiCaller(array $params) : array
    {
        $paramsJson = json_encode($params);
        $apiUrl = $this->scopeConfig->getValue(self::API_URL);
        $apiKey = $this->scopeConfig->getValue(self::API_KEY);

        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key'    => $apiKey,
        ];

        try {
            $this->logger->info('IncStoreShipping-API-Request: ' . $paramsJson);
            $response = $this->httpClient->post($apiUrl, [
                'headers' => $headers,
                'body'    => $paramsJson,
            ]);
            $responseJson = $response->getBody()->getContents();
            $this->logger->info('IncStoreShipping-API-Response: ' . $responseJson);
            $responseArray = json_decode($responseJson, true);
            if (isset($responseArray['errors'])) {
                $this->logger->error(json_encode($responseArray['errors']));
                return ['isSuccessful' => false];
            }
            return $responseArray;
        } catch (RequestException $e) {
            $this->logger->error('IncStoreShipping-API-RequestError: ' . $e->getMessage());
            return ['isSuccessful' => false];
        }
    }
}
