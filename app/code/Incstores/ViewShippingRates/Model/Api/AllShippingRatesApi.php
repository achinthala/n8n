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

class AllShippingRatesApi
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
    private const DEFAULT_TIMEOUT = 45; // Longer timeout for complex requests

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
     * Get shipping rates for product/quote requests via /all endpoint
     *
     * @param array $requestData
     * @return array
     */
    public function getAllRates(array $requestData): array
    {
        $endpoint = self::API_BASE_URL . '/all';

        $this->logger->info('AllShippingRatesApi - Starting request', [
            'endpoint' => $endpoint,
            'transaction_id' => $requestData['transactionId'] ?? 'none',
            'line_items_count' => count($requestData['lineItems'] ?? []),
            'delivery_zip' => $requestData['shipToAddress']['zipCode'] ?? 'missing'
        ]);


        try {
            // Validate and clean request data
            $cleanedRequest = $this->validateAndCleanRequest($requestData);

            // Serialize request data
            $jsonData = $this->jsonSerializer->serialize($cleanedRequest);

            // Log the actual request being sent for debugging
            $this->logger->info('AllShippingRatesApi - Request payload', [
                'json_data' => $jsonData
            ]);

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
                'connect_timeout' => 15
            ]);

            $httpCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            $this->logger->info('AllShippingRatesApi - Response received', [
                'http_code' => $httpCode,
                'response_size' => strlen($responseBody)
            ]);

            // Handle non-200 responses
            if ($httpCode !== 200) {
                $this->logger->error('AllShippingRatesApi - HTTP error', [
                    'http_code' => $httpCode,
                    'response_body' => substr($responseBody, 0, 500)
                ]);
                return $this->buildErrorResponse('API returned HTTP status: ' . $httpCode, $httpCode);
            }

            // Parse response
            $responseData = $this->jsonSerializer->unserialize($responseBody);

            if (!$responseData) {
                $this->logger->error('AllShippingRatesApi - Invalid JSON response');
                return $this->buildErrorResponse('Invalid API response format', 500);
            }

            // Log response summary
            $this->logResponseSummary($responseData);

            // Return raw API response (will be processed by AllResponseTransformer)
            return $responseData;

        } catch (ConnectException $e) {
            $this->logger->error('AllShippingRatesApi - Connection failed', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);
            return $this->buildErrorResponse('Unable to connect to shipping API', 503);

        } catch (ClientException $e) {
            $httpCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 400;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
            $this->logger->error('AllShippingRatesApi - Client error', [
                'http_code' => $httpCode,
                'message' => $e->getMessage(),
                'response_body' => $responseBody
            ]);
            return $this->buildErrorResponse('API request error: ' . $httpCode, $httpCode);

        } catch (ServerException $e) {
            $httpCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            $this->logger->error('AllShippingRatesApi - Server error', [
                'http_code' => $httpCode,
                'message' => $e->getMessage()
            ]);
            return $this->buildErrorResponse('API server error: ' . $httpCode, $httpCode);

        } catch (RequestException $e) {
            $this->logger->error('AllShippingRatesApi - Request exception', [
                'message' => $e->getMessage(),
                'type' => get_class($e)
            ]);

            // For now, return not implemented response until /all endpoint is ready
            return $this->buildNotImplementedResponse();

        } catch (\Exception $e) {
            $this->logger->error('AllShippingRatesApi - Unexpected exception', [
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
            'shipToAddress.zipCode',
            'lineItems',
            'deliveryOptions'
        ];

        foreach ($requiredFields as $field) {
            $value = $this->getNestedValue($requestData, $field);
            if (empty($value)) {
                throw new \InvalidArgumentException("Required field missing: {$field}");
            }
        }

        // Validate line items
        if (empty($requestData['lineItems']) || !is_array($requestData['lineItems'])) {
            throw new \InvalidArgumentException("Line items must be a non-empty array");
        }

        foreach ($requestData['lineItems'] as $lineItem) {
            if (empty($lineItem['sku'])) {
                throw new \InvalidArgumentException("Line item SKU is required");
            }
            if (empty($lineItem['quantity']) || !is_numeric($lineItem['quantity']) || $lineItem['quantity'] <= 0) {
                throw new \InvalidArgumentException("Invalid line item quantity");
            }
            if (empty($lineItem['unitWeight']) || !is_numeric($lineItem['unitWeight']) || $lineItem['unitWeight'] <= 0) {
                throw new \InvalidArgumentException("Invalid line item unit weight");
            }
        }

        // Validate delivery options
        $deliveryOptions = $requestData['deliveryOptions'];
        if (!is_array($deliveryOptions)) {
            throw new \InvalidArgumentException("Delivery options must be an array");
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
        $shipmentsCount = 0;

        if (isset($responseData['data']['lineItemShipments']) && is_array($responseData['data']['lineItemShipments'])) {
            $shipmentsCount = count($responseData['data']['lineItemShipments']);
        }

        $this->logger->info('AllShippingRatesApi - Response summary', [
            'successful' => $successful,
            'shipments_count' => $shipmentsCount,
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
            'data' => [
                'lineItemShipments' => []
            ]
        ];
    }

    /**
     * Build not implemented response (temporary until /all endpoint is ready)
     *
     * @return array
     */
    private function buildNotImplementedResponse(): array
    {
        $this->logger->info('AllShippingRatesApi - Returning not implemented response (API not ready)');

        return [
            'isSuccessful' => false,
            'message' => 'The /all endpoint is not yet implemented. Please use mock data for testing product/quote requests.',
            'statusCode' => 501,
            'data' => [
                'lineItemShipments' => []
            ]
        ];
    }
}
