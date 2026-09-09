<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Api;

use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class MockShippingRatesApi
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
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Get shipping rates for product/quote requests (mock implementation)
     *
     * @param array $requestData
     * @return array
     */
    public function getAllRates(array $requestData): array
    {
        $this->logger->info('ViewShippingRates Mock API - getAllRates called with data: ' . $this->jsonSerializer->serialize($requestData));

        // Simulate API processing time
        sleep(1);

        // Generate mock response based on request type
        $requestType = $requestData['requestType'] ?? 'product';
        
        if ($requestType === 'simple') {
            return $this->getSimpleRequestPlaceholder($requestData);
        }

        return $this->generateMockProductQuoteResponse($requestData);
    }

    /**
     * Get placeholder response for simple requests
     *
     * @param array $requestData
     * @return array
     */
    private function getSimpleRequestPlaceholder(array $requestData): array
    {
        return [
            'isSuccessful' => false,
            'message' => 'Simple requests are not yet implemented. The /simple endpoint is still under development.',
            'statusCode' => 501,
            'lineItemShipments' => []
        ];
    }

    /**
     * Generate mock response for product/quote requests
     *
     * @param array $requestData
     * @return array
     */
    private function generateMockProductQuoteResponse(array $requestData): array
    {
        $shipToZip = $requestData['shipToAddress']['zipCode'] ?? '90210';
        $isResidential = $requestData['deliveryOptions']['isResidential'] ?? true;
        $hasLiftGate = $requestData['deliveryOptions']['isLiftGateRequired'] ?? true;
        
        // Base pricing that varies by delivery options
        $basePrice = 200.00;
        if ($isResidential) {
            $basePrice += 50.00;
        }
        if ($hasLiftGate) {
            $basePrice += 75.00;
        }

        // Mock carriers with different pricing structures
        $carriers = [
            [
                'carrierName' => 'FedEx Freight',
                'baseCost' => $basePrice * 1.10,
                'baseCharge' => $basePrice * 1.25,
                'message' => 'Standard LTL delivery - 5-7 business days'
            ],
            [
                'carrierName' => 'UPS Freight',
                'baseCost' => $basePrice * 1.05,
                'baseCharge' => $basePrice * 1.20,
                'message' => 'Economy LTL delivery - 7-10 business days'
            ],
            [
                'carrierName' => 'XPO Logistics',
                'baseCost' => $basePrice * 1.15,
                'baseCharge' => $basePrice * 1.30,
                'message' => 'Premium LTL delivery - 3-5 business days'
            ],
            [
                'carrierName' => 'Old Dominion',
                'baseCost' => $basePrice * 1.08,
                'baseCharge' => $basePrice * 1.22,
                'message' => 'Regional LTL delivery - 5-8 business days'
            ]
        ];

        // Generate fulfillment locations based on destination
        $fulfillmentLocations = $this->getMockFulfillmentLocations($shipToZip);
        
        $lineItemShipments = [];
        $lineItemId = '1';
        
        // Create shipment options for each carrier
        foreach ($carriers as $carrier) {
            foreach ($fulfillmentLocations as $location) {
                // Add some variation based on distance
                $distanceMultiplier = $location['distanceMultiplier'];
                $cost = round($carrier['baseCost'] * $distanceMultiplier, 2);
                $charge = round($carrier['baseCharge'] * $distanceMultiplier, 2);
                
                $lineItemShipments[] = [
                    'carrierName' => $carrier['carrierName'],
                    'cost' => $cost,
                    'charge' => $charge,
                    'message' => $carrier['message'],
                    'lineItem' => ['id' => $lineItemId],
                    'fulfilmentLocation' => [
                        'name' => $location['name'],
                        'zipCode' => $location['zipCode']
                    ]
                ];
            }
        }

        return [
            'isSuccessful' => true,
            'message' => 'Mock API response generated successfully',
            'statusCode' => 200,
            'lineItemShipments' => $lineItemShipments
        ];
    }

    /**
     * Get mock fulfillment locations based on destination zip
     *
     * @param string $destinationZip
     * @return array
     */
    private function getMockFulfillmentLocations(string $destinationZip): array
    {
        $zipPrefix = substr($destinationZip, 0, 1);
        
        // Determine primary fulfillment location based on destination
        switch ($zipPrefix) {
            case '0':
            case '1':
            case '2':
                // East Coast
                return [
                    [
                        'name' => 'Atlanta Warehouse',
                        'zipCode' => '30309',
                        'distanceMultiplier' => 1.0
                    ],
                    [
                        'name' => 'Charlotte Distribution Center',
                        'zipCode' => '28202',
                        'distanceMultiplier' => 1.15
                    ]
                ];
            case '3':
            case '4':
            case '5':
                // Central
                return [
                    [
                        'name' => 'Dallas Distribution Center',
                        'zipCode' => '75201',
                        'distanceMultiplier' => 1.0
                    ],
                    [
                        'name' => 'Chicago Warehouse',
                        'zipCode' => '60601',
                        'distanceMultiplier' => 1.10
                    ]
                ];
            case '6':
            case '7':
                // Mountain/Plains
                return [
                    [
                        'name' => 'Denver Distribution Center',
                        'zipCode' => '80202',
                        'distanceMultiplier' => 1.0
                    ]
                ];
            case '8':
            case '9':
                // West Coast
                return [
                    [
                        'name' => 'Los Angeles Warehouse',
                        'zipCode' => '90028',
                        'distanceMultiplier' => 1.0
                    ],
                    [
                        'name' => 'Phoenix Distribution Center',
                        'zipCode' => '85003',
                        'distanceMultiplier' => 1.20
                    ]
                ];
            default:
                // Default location
                return [
                    [
                        'name' => 'Main Distribution Center',
                        'zipCode' => '30309',
                        'distanceMultiplier' => 1.0
                    ]
                ];
        }
    }

    /**
     * Simulate API error for testing
     *
     * @param array $requestData
     * @return array
     */
    public function simulateError(array $requestData): array
    {
        $this->logger->warning('ViewShippingRates Mock API - simulating error');
        
        return [
            'isSuccessful' => false,
            'message' => 'Mock API error: Unable to calculate shipping rates at this time',
            'statusCode' => 500,
            'lineItemShipments' => []
        ];
    }
}