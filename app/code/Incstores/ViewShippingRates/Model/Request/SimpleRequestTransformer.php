<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model\Request;

use Magento\Framework\Exception\LocalizedException;

class SimpleRequestTransformer
{
    /**
     * Transform form data to Simple API request format
     *
     * @param array $formData
     * @return array
     * @throws LocalizedException
     */
    public function transform(array $formData): array
    {
        $this->validateSimpleRequest($formData);

        return [
            'pickupFrom' => [
                'address' => [
                    'street' => $formData['ship_from_street'] ?? '',
                    'city' => $formData['ship_from_city'] ?? '',
                    'state' => $formData['ship_from_state'] ?? '',
                    'zipCode' => $formData['ship_from_zipcode']
                ]
            ],
            'deliverTo' => [
                'street' => $formData['ship_to_street'] ?? '',
                'city' => $formData['ship_to_city'] ?? '',
                'state' => $formData['ship_to_state'] ?? '',
                'zipCode' => $formData['ship_to_zipcode']
            ],
            'deliveryOptions' => [
                'deliveryNotification' => !empty($formData['delivery_notification']),
                'isLiftGateRequired' => !empty($formData['lift_gate_required']),
                'isResidential' => !empty($formData['residential'])
            ],
            'weightClass' => $formData['weight_class'],
            'packages' => [
                [
                    'units' => 1, // Simple requests always use 1 package
                    'unitWeight' => (int) $formData['total_weight']
                ]
            ]
        ];
    }

    /**
     * Validate simple request form data
     *
     * @param array $formData
     * @throws LocalizedException
     */
    private function validateSimpleRequest(array $formData): void
    {
        $requiredFields = [
            'ship_to_zipcode' => __('Ship to zipcode is required'),
            'ship_from_zipcode' => __('Ship from zipcode is required'),
            'weight_class' => __('Weight class is required for simple requests'),
            'total_weight' => __('Total weight is required for simple requests')
        ];

        foreach ($requiredFields as $field => $errorMessage) {
            if (empty($formData[$field])) {
                throw new LocalizedException($errorMessage);
            }
        }

        // Validate weight is a positive integer
        if (!is_numeric($formData['total_weight']) || (int) $formData['total_weight'] <= 0) {
            throw new LocalizedException(__('Total weight must be a positive number'));
        }

        // Validate weight class format
        if (!preg_match('/^CLASS_\d+(?:_\d+)?$/', $formData['weight_class'])) {
            throw new LocalizedException(__('Invalid weight class format'));
        }
    }
}