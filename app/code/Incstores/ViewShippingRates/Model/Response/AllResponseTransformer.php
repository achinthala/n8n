<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Response;

class AllResponseTransformer
{
    /**
     * Transform All API response to standardized format
     *
     * @param array $apiResponse
     * @param array $originalLineItems Original line items from request (optional)
     * @return array
     */
    public function transform(array $apiResponse, array $originalLineItems = []): array
    {
        $lineItemShipments = [];

        // Create lookup map of original line items by ID
        $lineItemLookup = [];
        foreach ($originalLineItems as $item) {
            if (isset($item['id'])) {
                $lineItemLookup[$item['id']] = $item;
            }
        }

        // The /all endpoint already returns lineItemShipments in the correct format
        if (isset($apiResponse['data']['lineItemShipments']) && is_array($apiResponse['data']['lineItemShipments'])) {
            $lineItemShipments = array_map(
                function($shipment) use ($lineItemLookup) {
                    return $this->normalizeLineItemShipment($shipment, $lineItemLookup);
                },
                $apiResponse['data']['lineItemShipments']
            );
        }

        return [
            'isSuccessful' => $apiResponse['isSuccessful'] ?? false,
            'message' => $apiResponse['message'] ?? null,
            'statusCode' => 200,
            'lineItemShipments' => $lineItemShipments
        ];
    }

    /**
     * Normalize line item shipment data
     *
     * @param array $shipment
     * @param array $lineItemLookup
     * @return array
     */
    private function normalizeLineItemShipment(array $shipment, array $lineItemLookup = []): array
    {
        // Standardize fulfillment location naming
        $fulfillmentLocation = $shipment['fulfillmentLocation'] ?? $shipment['fulfilmentLocation'] ?? [];
        
        $normalized = [
            'isValid' => $shipment['isValid'] ?? true,
            'carrierName' => $shipment['carrierName'] ?? 'Unknown Carrier',
            'cost' => (float) ($shipment['cost'] ?? 0),
            'charge' => (float) ($shipment['charge'] ?? 0),
            'message' => $this->buildEnhancedMessage($shipment),
            'lineItem' => $this->normalizeLineItem($shipment['lineItem'] ?? $shipment['lineItemId'] ?? [], $lineItemLookup),
            'fulfilmentLocation' => $this->normalizeFulfillmentLocation($fulfillmentLocation)
        ];

        // Add additional fields that might be present
        if (isset($shipment['actualCarrierName'])) {
            $normalized['actualCarrierName'] = $shipment['actualCarrierName'];
        }

        if (isset($shipment['estimatedDeliveryDate'])) {
            $normalized['estimatedDeliveryDate'] = $shipment['estimatedDeliveryDate'];
        }

        if (isset($shipment['serviceLevel'])) {
            $normalized['serviceLevel'] = $shipment['serviceLevel'];
        }

        return $normalized;
    }

    /**
     * Normalize line item data
     *
     * @param mixed $lineItem
     * @param array $lineItemLookup
     * @return array
     */
    private function normalizeLineItem($lineItem, array $lineItemLookup = []): array
    {
        $lineItemId = null;
        
        // Extract the line item ID
        if (is_string($lineItem)) {
            $lineItemId = $lineItem;
        } elseif (is_array($lineItem)) {
            $lineItemId = $lineItem['id'] ?? $lineItem['lineItemId'] ?? null;
        }

        // If we have the original line item data, merge it in
        if ($lineItemId && isset($lineItemLookup[$lineItemId])) {
            $originalItem = $lineItemLookup[$lineItemId];
            return [
                'id' => $lineItemId,
                'sku' => $originalItem['sku'] ?? null,
                'name' => $originalItem['name'] ?? null,
                'quantity' => $originalItem['quantity'] ?? null,
                'unitWeight' => $originalItem['unitWeight'] ?? null
            ];
        }

        // Fallback to API response data if available
        if (is_array($lineItem)) {
            return [
                'id' => $lineItem['id'] ?? $lineItem['lineItemId'] ?? 'Unknown',
                'sku' => $lineItem['sku'] ?? null,
                'name' => $lineItem['name'] ?? null,
                'quantity' => $lineItem['quantity'] ?? null,
                'unitWeight' => $lineItem['unitWeight'] ?? null
            ];
        }

        return ['id' => $lineItemId ?? 'Unknown'];
    }

    /**
     * Normalize fulfillment location data
     *
     * @param array $location
     * @return array
     */
    private function normalizeFulfillmentLocation(array $location): array
    {
        $address = $location['address'] ?? [];
        
        return [
            'name' => $location['name'] ?? 'Distribution Center',
            'zipCode' => $address['zipCode'] ?? $location['zipCode'] ?? 'Unknown',
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'street' => $address['street'] ?? null,
            'address' => $address
        ];
    }

    /**
     * Build enhanced message with additional context
     *
     * @param array $shipment
     * @return string
     */
    private function buildEnhancedMessage(array $shipment): string
    {
        $messages = [];
        
        // Start with the base message
        if (!empty($shipment['message'])) {
            $messages[] = $shipment['message'];
        }

        // Add service level if available
        if (!empty($shipment['serviceLevel']) && $shipment['serviceLevel'] !== 'Standard') {
            $messages[] = $shipment['serviceLevel'] . ' service';
        }

        // Add delivery estimate if available
        if (!empty($shipment['estimatedDeliveryDate'])) {
            $messages[] = 'Est. delivery: ' . $shipment['estimatedDeliveryDate'];
        }

        // Add fulfillment location context
        $fulfillmentLocation = $shipment['fulfillmentLocation'] ?? $shipment['fulfilmentLocation'] ?? [];
        if (!empty($fulfillmentLocation['name']) && $fulfillmentLocation['name'] !== 'Distribution Center') {
            $locationName = $fulfillmentLocation['name'];
            $locationZip = $fulfillmentLocation['address']['zipCode'] ?? $fulfillmentLocation['zipCode'] ?? '';
            if ($locationZip) {
                $messages[] = 'Ships from ' . $locationName . ' (' . $locationZip . ')';
            } else {
                $messages[] = 'Ships from ' . $locationName;
            }
        }

        // Add validity warning if shipment is invalid
        if (isset($shipment['isValid']) && !$shipment['isValid']) {
            array_unshift($messages, 'Invalid shipment');
        }

        return !empty($messages) ? implode(' - ', $messages) : 'Standard shipping';
    }

    /**
     * Sort line item shipments by cost (ascending)
     *
     * @param array $lineItemShipments
     * @return array
     */
    public function sortByCost(array $lineItemShipments): array
    {
        usort($lineItemShipments, function ($a, $b) {
            return ($a['cost'] ?? 0) <=> ($b['cost'] ?? 0);
        });

        return $lineItemShipments;
    }

    /**
     * Group line item shipments by carrier
     *
     * @param array $lineItemShipments
     * @return array
     */
    public function groupByCarrier(array $lineItemShipments): array
    {
        $grouped = [];
        
        foreach ($lineItemShipments as $shipment) {
            $carrierName = $shipment['carrierName'] ?? 'Unknown';
            if (!isset($grouped[$carrierName])) {
                $grouped[$carrierName] = [];
            }
            $grouped[$carrierName][] = $shipment;
        }

        return $grouped;
    }

    /**
     * Filter valid shipments only
     *
     * @param array $lineItemShipments
     * @return array
     */
    public function filterValidShipments(array $lineItemShipments): array
    {
        return array_filter($lineItemShipments, function ($shipment) {
            return ($shipment['isValid'] ?? true) && ($shipment['cost'] ?? 0) > 0;
        });
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