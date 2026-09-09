<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Request;

class OrderRequestTransformer
{
    /**
     * Transform order data to /all API request format
     *
     * @param array $orderData
     * @param array $formData
     * @return array
     */
    public function transform(array $orderData, array $formData = []): array
    {
        $transactionId = $formData['transaction_id'] ?? 'order-' . $orderData['orderId'];
        
        // Build ship to address from order shipping address
        $shipToAddress = [
            'zipCode' => $orderData['shippingAddress']['zipCode'] ?? '',
            'street' => $orderData['shippingAddress']['street'] ?? '',
            'city' => $orderData['shippingAddress']['city'] ?? '',
            'state' => $orderData['shippingAddress']['state'] ?? '',
            'country' => $orderData['shippingAddress']['country'] ?? 'US'
        ];

        // Build line items from order items
        $lineItems = [];
        foreach ($orderData['lineItems'] as $item) {
            $lineItems[] = [
                'id' => $item['id'],
                'sku' => $item['sku'],
                'name' => $item['name'] ?? '',
                'quantity' => $item['quantity'],
                'unitWeight' => $item['unitWeight']
            ];
        }

        // Set default delivery options - these can be overridden by form data
        $deliveryOptions = [
            'isResidential' => !empty($formData['residential']),
            'isLiftGateRequired' => !empty($formData['lift_gate_required']),
            'deliveryNotification' => !empty($formData['delivery_notification'])
        ];

        // Build the request data structure expected by the /all endpoint
        $requestData = [
            'transactionId' => $transactionId,
            'shipToAddress' => $shipToAddress,
            'lineItems' => $lineItems,
            'deliveryOptions' => $deliveryOptions,
            'orderInfo' => [
                'orderId' => $orderData['orderId'],
                'incrementId' => $orderData['incrementId'],
                'customerEmail' => $orderData['customerEmail'],
                'isOrder' => $orderData['isOrder'] ?? true,
                'status' => $orderData['status'] ?? null,
                'state' => $orderData['state'] ?? null,
                'itemCount' => $orderData['itemCount']
            ]
        ];

        return $requestData;
    }

    /**
     * Validate order data for transformation
     *
     * @param array $orderData
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $orderData): array
    {
        $errors = [];

        // Validate required order fields
        if (empty($orderData['orderId'])) {
            $errors[] = 'Order ID is required';
        }

        if (empty($orderData['lineItems'])) {
            $errors[] = 'Order must have line items';
        }

        if (empty($orderData['shippingAddress']['zipCode'])) {
            $errors[] = 'Order must have a shipping address with zip code';
        }

        // Validate line items
        if (!empty($orderData['lineItems'])) {
            foreach ($orderData['lineItems'] as $index => $item) {
                if (empty($item['sku'])) {
                    $errors[] = "Line item {$index} is missing SKU";
                }
                // Note: Product name validation is optional for backward compatibility
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    $errors[] = "Line item {$index} has invalid quantity";
                }
                if (empty($item['unitWeight']) || $item['unitWeight'] <= 0) {
                    $errors[] = "Line item {$index} has invalid weight";
                }
            }
        }

        return $errors;
    }
}