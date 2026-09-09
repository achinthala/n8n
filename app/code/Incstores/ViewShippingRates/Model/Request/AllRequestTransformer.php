<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Request;

use Magento\Framework\Exception\LocalizedException;
use Incstores\ViewShippingRates\Model\QuoteProvider;

class AllRequestTransformer
{
    /**
     * @var QuoteProvider
     */
    private $quoteProvider;

    /**
     * @param QuoteProvider $quoteProvider
     */
    public function __construct(QuoteProvider $quoteProvider)
    {
        $this->quoteProvider = $quoteProvider;
    }

    /**
     * Transform form data to All API request format
     *
     * @param array $formData
     * @return array
     * @throws LocalizedException
     */
    public function transform(array $formData): array
    {
        $requestType = $formData['request_type'] ?? '';
        
        if ($requestType === 'product') {
            return $this->transformProductRequest($formData);
        } elseif ($requestType === 'quote') {
            return $this->transformQuoteRequest($formData);
        } else {
            throw new LocalizedException(__('Invalid request type for All API: %1', $requestType));
        }
    }

    /**
     * Transform product request data
     *
     * @param array $formData
     * @return array
     * @throws LocalizedException
     */
    private function transformProductRequest(array $formData): array
    {
        $this->validateProductRequest($formData);

        return [
            'transactionId' => $formData['transaction_id'] ?? null,
            'shipToAddress' => [
                'street' => $formData['ship_to_street'] ?? '',
                'city' => $formData['ship_to_city'] ?? '',
                'state' => $formData['ship_to_state'] ?? '',
                'zipCode' => $formData['ship_to_zipcode'],
                'country' => 'US'
            ],
            'deliveryOptions' => [
                'isResidential' => !empty($formData['residential']),
                'isLiftGateRequired' => !empty($formData['lift_gate_required']),
                'deliveryNotification' => !empty($formData['delivery_notification'])
            ],
            'lineItems' => [
                [
                    'id' => '1',
                    'sku' => $formData['sku'],
                    'quantity' => (int) $formData['quantity'],
                    'unitWeight' => (int) $formData['unit_weight']
                ]
            ]
        ];
    }

    /**
     * Transform quote request data
     *
     * @param array $formData
     * @return array
     * @throws LocalizedException
     */
    private function transformQuoteRequest(array $formData): array
    {
        $this->validateQuoteRequest($formData);

        // Get quote data
        $quoteData = $this->quoteProvider->getQuoteByNumber($formData['quote_number']);
        
        if (!$quoteData) {
            throw new LocalizedException(__('Quote not found: %1', $formData['quote_number']));
        }

        $apiRequest = [
            'transactionId' => $formData['transaction_id'] ?: $quoteData['reservedOrderId'],
            'shipToAddress' => [
                'street' => $formData['ship_to_street'] ?? '',
                'city' => $formData['ship_to_city'] ?? '',
                'state' => $formData['ship_to_state'] ?? '',
                'zipCode' => $formData['ship_to_zipcode'],
                'country' => 'US'
            ],
            'deliveryOptions' => [
                'isResidential' => !empty($formData['residential']),
                'isLiftGateRequired' => !empty($formData['lift_gate_required']),
                'deliveryNotification' => !empty($formData['delivery_notification'])
            ],
            'lineItems' => $quoteData['lineItems']
        ];

        // Use quote shipping address if not provided in form
        if (empty($apiRequest['shipToAddress']['zipCode']) && !empty($quoteData['shippingAddress']['zipCode'])) {
            $apiRequest['shipToAddress'] = array_merge(
                $apiRequest['shipToAddress'], 
                $quoteData['shippingAddress']
            );
        }

        return $apiRequest;
    }

    /**
     * Validate product request form data
     *
     * @param array $formData
     * @throws LocalizedException
     */
    private function validateProductRequest(array $formData): void
    {
        $requiredFields = [
            'ship_to_zipcode' => __('Ship to zipcode is required'),
            'sku' => __('SKU is required for product requests'),
            'quantity' => __('Quantity is required for product requests'),
            'unit_weight' => __('Unit weight is required for product requests')
        ];

        foreach ($requiredFields as $field => $errorMessage) {
            if (empty($formData[$field])) {
                throw new LocalizedException($errorMessage);
            }
        }

        // Validate numeric fields
        if (!is_numeric($formData['quantity']) || (int) $formData['quantity'] <= 0) {
            throw new LocalizedException(__('Quantity must be a positive number'));
        }

        if (!is_numeric($formData['unit_weight']) || (int) $formData['unit_weight'] <= 0) {
            throw new LocalizedException(__('Unit weight must be a positive number'));
        }
    }

    /**
     * Validate quote request form data
     *
     * @param array $formData
     * @throws LocalizedException
     */
    private function validateQuoteRequest(array $formData): void
    {
        if (empty($formData['quote_number'])) {
            throw new LocalizedException(__('Quote number is required for quote requests'));
        }
    }
}