<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Request;

class QuoteRequestTransformer
{
    /**
     * Transform quote data to /all API request format
     *
     * @param array $quoteData
     * @param array $formData
     * @return array
     */
    public function transform(array $quoteData, array $formData = []): array
    {
        $transactionId = $formData['transaction_id'] ?? 'quote-' . $quoteData['quoteId'];
        
        // Build ship to address from quote shipping address
        $shipToAddress = [
            'zipCode' => $quoteData['shippingAddress']['zipCode'] ?? '',
            'street' => $quoteData['shippingAddress']['street'] ?? '',
            'city' => $quoteData['shippingAddress']['city'] ?? '',
            'state' => $quoteData['shippingAddress']['state'] ?? '',
            'country' => $quoteData['shippingAddress']['country'] ?? 'US'
        ];

        // Build line items from quote items
        $lineItems = [];
        foreach ($quoteData['lineItems'] as $item) {
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
            'quoteInfo' => [
                'quoteId' => $quoteData['quoteId'],
                'reservedOrderId' => $quoteData['reservedOrderId'],
                'customerEmail' => $quoteData['customerEmail'],
                'isAmastyQuote' => $quoteData['isAmastyQuote'] ?? false,
                'status' => $quoteData['status'] ?? null,
                'itemCount' => $quoteData['itemCount']
            ]
        ];

        return $requestData;
    }

    /**
     * Validate quote data for transformation
     *
     * @param array $quoteData
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $quoteData): array
    {
        $errors = [];

        // Validate required quote fields
        if (empty($quoteData['quoteId'])) {
            $errors[] = 'Quote ID is required';
        }

        if (empty($quoteData['lineItems'])) {
            $errors[] = 'Quote must have line items';
        }

        if (empty($quoteData['shippingAddress']['zipCode'])) {
            $errors[] = 'Quote must have a shipping address with zip code';
        }

        // Validate line items
        if (!empty($quoteData['lineItems'])) {
            foreach ($quoteData['lineItems'] as $index => $item) {
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