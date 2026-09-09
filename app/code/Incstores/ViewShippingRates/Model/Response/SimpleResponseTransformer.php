<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Response;

class SimpleResponseTransformer
{
    /**
     * Transform simple API response to standardized format
     *
     * @param array $apiResponse
     * @return array
     */
    public function transform(array $apiResponse): array
    {
        $lineItemShipments = [];

        if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
            foreach ($apiResponse['data'] as $carrierData) {
                $lineItemShipments = array_merge(
                    $lineItemShipments,
                    $this->processCarrierData($carrierData)
                );
            }
        }

        return [
            'isSuccessful' => $apiResponse['isSuccessful'] ?? false,
            'message' => $apiResponse['message'] ?? null,
            'statusCode' => 200,
            'lineItemShipments' => $lineItemShipments
        ];
    }

    /**
     * Process individual carrier data
     *
     * @param array $carrierData
     * @return array
     */
    private function processCarrierData(array $carrierData): array
    {
        $shipments = [];
        $carrierName = $carrierData['carrier'] ?? 'Unknown Carrier';

        // Handle invalid carriers
        if (!$carrierData['isValid']) {
            $shipments[] = [
                'isValid' => false,
                'carrierName' => $carrierName,
                'actualCarrierName' => $carrierData['actualCarrierName'] ?? $carrierName,
                'cost' => 0,
                'charge' => 0,
                'message' => $carrierData['message'] ?? 'Carrier returned invalid response',
                'lineItem' => ['id' => '1'],
                'fulfilmentLocation' => [
                    'name' => 'Unknown',
                    'zipCode' => $this->extractPickupZipCode($carrierData)
                ]
            ];

            return $shipments;
        }

        // Process valid carrier package rates
        if (isset($carrierData['packageRates']) && is_array($carrierData['packageRates'])) {
            foreach ($carrierData['packageRates'] as $packageRate) {
                $shipments[] = [
                    'isValid' => true,
                    'carrierName' => $carrierName,
                    'actualCarrierName' => $carrierData['actualCarrierName'] ?? $carrierName,
                    'cost' => $packageRate['cost'] ?? 0,
                    'charge' => $packageRate['charge'] ?? 0,
                    'message' => $this->buildMessage($carrierData, $packageRate),
                    'lineItem' => ['id' => $packageRate['package']['lineItemId'] ?? '1'],
                    'fulfilmentLocation' => $this->extractFulfillmentLocation($carrierData),
                    'package' => $this->extractPackageInfo($packageRate['package'] ?? []),
                    'shipToAddress' => $this->extractShipToAddress($carrierData)
                ];
            }
        }

        return $shipments;
    }

    /**
     * Extract fulfillment location from carrier data
     *
     * @param array $carrierData
     * @return array
     */
    private function extractFulfillmentLocation(array $carrierData): array
    {
        $pickupFrom = $carrierData['pickUpFrom'] ?? [];

        return [
            'name' => $pickupFrom['name'] ?? 'Distribution Center',
            'zipCode' => $this->extractPickupZipCode($carrierData),
            'address' => $pickupFrom['address'] ?? []
        ];
    }

    /**
     * Extract pickup zip code from carrier data
     *
     * @param array $carrierData
     * @return string
     */
    private function extractPickupZipCode(array $carrierData): string
    {
        return $carrierData['pickUpFrom']['address']['zipCode']
            ?? $carrierData['shipToAddress']['zipCode']
            ?? 'Unknown';
    }

    /**
     * Extract ship to address from carrier data
     *
     * @param array $carrierData
     * @return array
     */
    private function extractShipToAddress(array $carrierData): array
    {
        return $carrierData['shipToAddress'] ?? [];
    }

    /**
     * Extract package information
     *
     * @param array $packageData
     * @return array
     */
    private function extractPackageInfo(array $packageData): array
    {
        return [
            'units' => $packageData['units'] ?? 1,
            'unitWeight' => $packageData['unitWeight'] ?? 0,
            'productType' => $packageData['productType'] ?? 'Standard',
            'serviceLevel' => $packageData['serviceLevel'] ?? 'Standard',
            'dollarValue' => $packageData['dollarValue'] ?? 0
        ];
    }

    /**
     * Build descriptive message for the shipment
     *
     * @param array $carrierData
     * @param array $packageRate
     * @return string
     */
    private function buildMessage(array $carrierData, array $packageRate): string
    {
        $messages = [];

        // Add carrier-level message if available
        if (!empty($carrierData['message'])) {
            $messages[] = $carrierData['message'];
        }

//        // Add service level information
//        $package = $packageRate['package'] ?? [];
//        $serviceLevel = $package['serviceLevel'] ?? 'Standard';
//        if ($serviceLevel !== 'Standard') {
//            $messages[] = $serviceLevel . ' service';
//        }
//
//        // Add weight information
//        $units = $package['units'] ?? 1;
//        $unitWeight = $package['unitWeight'] ?? 0;
//        if ($units > 0 && $unitWeight > 0) {
//            $totalWeight = $units * $unitWeight;
//            $messages[] = $totalWeight . ' lbs total weight';
//        }

        return !empty($messages) ? implode(' - ', $messages) : '';
    }

    /**
     * Build error response for API failures
     *
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    public function buildErrorResponse(string $message, int $statusCode): array
    {
        return [
            'isSuccessful' => false,
            'message' => $message,
            'statusCode' => $statusCode,
            'lineItemShipments' => []
        ];
    }
}
