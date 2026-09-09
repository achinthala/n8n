<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Api;

use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class SimpleShippingRatesApi
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private const API_BASE_URL = 'https://api.flooringinc.net/shipping/rate';

    /**
     * @var string
     */
    private const API_KEY = '4077a2edec3446ef897ca66e7fcc5881';

    /**
     * @var int
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->httpClient = new Client();
    }

    /**
     * Get shipping rates for simple requests
     *
     * @param array $requestData
     * @return array
     */
    public function getSimpleRates(array $requestData): array
    {
        $endpoint = self::API_BASE_URL . '/simple';
        
        $this->logger->info('SimpleShippingRatesApi - Starting request', [
            'endpoint' => $endpoint,
            'pickup_zip' => $requestData['pickupFrom']['address']['zipCode'] ?? 'missing',
            'delivery_zip' => $requestData['deliverTo']['zipCode'] ?? 'missing',
            'weight_class' => $requestData['weightClass'] ?? 'missing'
        ]);

        try {
            // Validate and clean request data
            $cleanedRequest = $this->validateAndCleanRequest($requestData);
            
            // Serialize request data
            $jsonData = $this->jsonSerializer->serialize($cleanedRequest);
            
            // Prepare headers
            $headers = [
                'Content-Type' => 'application/json',
                'x-api-key' => self::API_KEY,
                'User-Agent' => 'Magento-ViewShippingRates/1.0'
            ];

            // Make API call
            $response = $this->httpClient->post($endpoint, [
                'headers' => $headers,
                'body' => $jsonData,
                'timeout' => self::DEFAULT_TIMEOUT,
                'connect_timeout' => 10
            ]);

            $httpCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            $this->logger->info('SimpleShippingRatesApi - Response received', [
                'http_code' => $httpCode,
                'response_size' => strlen($responseBody)
            ]);

            // Handle non-200 responses
            if ($httpCode !== 200) {
                $this->logger->error('SimpleShippingRatesApi - HTTP error', [
                    'http_code' => $httpCode,
                    'response_body' => substr($responseBody, 0, 500)
                ]);
                return $this->buildErrorResponse('API returned HTTP status: ' . $httpCode, $httpCode);
            }

            // Parse response
            $responseData = $this->jsonSerializer->unserialize($responseBody);

            if (!$responseData) {
                $this->logger->error('SimpleShippingRatesApi - Invalid JSON response');
                return $this->buildErrorResponse('Invalid API response format', 500);
            }

            // Log response summary
            $this->logResponseSummary($responseData);

            // Return raw API response (will be processed by SimpleResponseTransformer)
            return $responseData;

        } catch (ConnectException $e) {
            $this->logger->error('SimpleShippingRatesApi - Connection failed', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);
            return $this->buildErrorResponse('Unable to connect to shipping API', 503);

        } catch (ClientException $e) {
            $httpCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 400;
            $this->logger->error('SimpleShippingRatesApi - Client error', [
                'http_code' => $httpCode,
                'message' => $e->getMessage()
            ]);
            return $this->buildErrorResponse('API request error: ' . $httpCode, $httpCode);

        } catch (ServerException $e) {
            $httpCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            $this->logger->error('SimpleShippingRatesApi - Server error', [
                'http_code' => $httpCode,
                'message' => $e->getMessage()
            ]);
            return $this->buildErrorResponse('API server error: ' . $httpCode, $httpCode);

        } catch (RequestException $e) {
            $this->logger->error('SimpleShippingRatesApi - Request exception', [
                'message' => $e->getMessage(),
                'type' => get_class($e)
            ]);
            return $this->buildErrorResponse('API request failed: ' . $e->getMessage(), 500);

        } catch (\Exception $e) {
            $this->logger->error('SimpleShippingRatesApi - Unexpected exception', [
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->buildErrorResponse('Unexpected error occurred', 500);
        }
    }

    /**
     * Validate and clean request data
     *
     * @param array $requestData
     * @return array
     * @throws \InvalidArgumentException
     */
    private function validateAndCleanRequest(array $requestData): array
    {
        // Required fields validation
        $requiredFields = [
            'pickupFrom.address.zipCode',
            'deliverTo.zipCode',
            'weightClass',
            'packages'
        ];

        foreach ($requiredFields as $field) {
            $value = $this->getNestedValue($requestData, $field);
            if (empty($value)) {
                throw new \InvalidArgumentException("Required field missing: {$field}");
            }
        }

        // Validate weight class format
        if (!preg_match('/^CLASS_\d+(?:_\d+)?$/', $requestData['weightClass'])) {
            throw new \InvalidArgumentException("Invalid weight class format: " . $requestData['weightClass']);
        }

        // Validate packages
        if (empty($requestData['packages']) || !is_array($requestData['packages'])) {
            throw new \InvalidArgumentException("Packages must be a non-empty array");
        }

        foreach ($requestData['packages'] as $package) {
            if (empty($package['unitWeight']) || !is_numeric($package['unitWeight']) || $package['unitWeight'] <= 0) {
                throw new \InvalidArgumentException("Invalid package unit weight");
            }
        }

        return $requestData;
    }

    /**
     * Get nested array value using dot notation
     *
     * @param array $array
     * @param string $key
     * @return mixed
     */
    private function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $keyPart) {
            if (!is_array($value) || !isset($value[$keyPart])) {
                return null;
            }
            $value = $value[$keyPart];
        }

        return $value;
    }

    /**
     * Log response summary for monitoring
     *
     * @param array $responseData
     */
    private function logResponseSummary(array $responseData): void
    {
        $successful = $responseData['isSuccessful'] ?? false;
        $carrierCount = 0;
        $validCarrierCount = 0;
        $rateCount = 0;

        if (isset($responseData['data']) && is_array($responseData['data'])) {
            $carrierCount = count($responseData['data']);
            
            foreach ($responseData['data'] as $carrier) {
                if ($carrier['isValid'] ?? false) {
                    $validCarrierCount++;
                    $rateCount += count($carrier['packageRates'] ?? []);
                }
            }
        }

        $this->logger->info('SimpleShippingRatesApi - Response summary', [
            'successful' => $successful,
            'total_carriers' => $carrierCount,
            'valid_carriers' => $validCarrierCount,
            'total_rates' => $rateCount,
            'message' => $responseData['message'] ?? null
        ]);
    }

    /**
     * Build error response
     *
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    private function buildErrorResponse(string $message, int $statusCode): array
    {
        return [
            'isSuccessful' => false,
            'message' => $message,
            'statusCode' => $statusCode,
            'data' => []
        ];
    }
}