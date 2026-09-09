<?php
/**
 * Copyright © Dcw. All rights reserved.
 * Service class for building attribute data for configurable products
 */
declare(strict_types=1);

namespace Dcw\MinimumPriceCron\Model;

use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchData;
use Psr\Log\LoggerInterface;

/**
 * Class AttributeDataBuilder
 * 
 * Builds attribute data arrays for bulk insert/update operations
 */
class AttributeDataBuilder
{
    /**
     * Shipping program priority order
     */
    private const SHIPPING_PRIORITY = [
        'next_day_free',
        'quick_ship_free',
        'free_ship',
        'next_day',
        'quick_ship'
    ];

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AdvanceSearchData
     */
    private AdvanceSearchData $advanceSearchData;

    /**
     * @param LoggerInterface $logger
     * @param AdvanceSearchData $advanceSearchData
     */
    public function __construct(
        LoggerInterface $logger,
        AdvanceSearchData $advanceSearchData
    ) {
        $this->logger = $logger;
        $this->advanceSearchData = $advanceSearchData;
    }

    /**
     * Build attribute data for configurable products based on minimum priced simple products
     *
     * @param array $configurableData
     * @param array $attributeIds
     * @return array
     */
    public function buildAttributeData(array $configurableData, array $attributeIds): array
    {
        $varcharData = [];
        $intData = [];

        foreach ($configurableData as $rowId => $data) {
            $simpleProducts = $data['simple_products'];

            if (empty($simpleProducts)) {
                continue;
            }

            // Calculate prices for ALL products using AdvanceSearch logic
            $calculatedPrices = [];
            $minCalculatedPrice = PHP_FLOAT_MAX;
            $minCalculatedProductId = null;

            foreach ($simpleProducts as $simpleId => $productData) {
                try {
                    // Get calculated price using AdvanceSearch (handles all calculator types)
                    $priceData = $this->advanceSearchData->getCalculatedPrice($simpleId, 'final');
                    
                    if (isset($priceData['price']) && is_numeric($priceData['price'])) {
                        $calculatedPrice = (float)$priceData['price'];
                        $calculatedPrices[$simpleId] = [
                            'price' => $calculatedPrice,
                            'calc_type' => $priceData['calType'] ?? 'each',
                            'is_pre_cut_roll' => ($productData['calculator_type'] ?? '') === 'pre_cut_roll'
                        ];

                        // Track overall minimum calculated price
                        if ($calculatedPrice < $minCalculatedPrice) {
                            $minCalculatedPrice = $calculatedPrice;
                            $minCalculatedProductId = $simpleId;
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning(
                        'Failed to calculate price for product',
                        ['simple_id' => $simpleId, 'error' => $e->getMessage()]
                    );
                }
            }

            if ($minCalculatedProductId === null) {
                continue;
            }

            // Find minimum from non-pre_cut_roll products
            $minNonPreCutPrice = PHP_FLOAT_MAX;
            $minNonPreCutProductId = null;

            foreach ($calculatedPrices as $simpleId => $priceInfo) {
                if (!$priceInfo['is_pre_cut_roll'] && $priceInfo['price'] < $minNonPreCutPrice) {
                    $minNonPreCutPrice = $priceInfo['price'];
                    $minNonPreCutProductId = $simpleId;
                }
            }

            // Find minimum from pre_cut_roll products
            $minPreCutPrice = PHP_FLOAT_MAX;
            $minPreCutProductId = null;

            foreach ($calculatedPrices as $simpleId => $priceInfo) {
                if ($priceInfo['is_pre_cut_roll'] && $priceInfo['price'] < $minPreCutPrice) {
                    $minPreCutPrice = $priceInfo['price'];
                    $minPreCutProductId = $simpleId;
                }
            }

            // Determine which product to use for attributes
            $winnerProductId = $minCalculatedProductId;
            $winnerPrice = $minCalculatedPrice;

            // If we have both non-pre_cut_roll and pre_cut_roll products, compare them
            if ($minNonPreCutProductId !== null && $minPreCutProductId !== null) {
                if ($minPreCutPrice < $minNonPreCutPrice) {
                    $winnerProductId = $minPreCutProductId;
                    $winnerPrice = $minPreCutPrice;
                } else {
                    $winnerProductId = $minNonPreCutProductId;
                    $winnerPrice = $minNonPreCutPrice;
                }
            }

            $winnerProductData = $simpleProducts[$winnerProductId];

            // Build varchar attribute data (width, length, coverage, height)
            $this->buildDimensionAttributes(
                $varcharData,
                $rowId,
                $winnerProductId,
                $winnerProductData,
                $attributeIds,
                false
            );

            // Build int attribute data (shipping program)
            $this->buildShippingProgramAttribute(
                $intData,
                $rowId,
                $simpleProducts,
                $attributeIds
            );
        }

        return [
            'varchar' => $varcharData,
            'int' => $intData
        ];
    }

    /**
     * Build dimension-related varchar attributes
     *
     * @param array $varcharData
     * @param int $rowId
     * @param int $minProductId
     * @param array $minProductData
     * @param array $attributeIds
     * @param bool $skipWidthLength Skip width/length if sqft will override
     * @return void
     */
    private function buildDimensionAttributes(
        array &$varcharData,
        int $rowId,
        int $minProductId,
        array $minProductData,
        array $attributeIds,
        bool $skipWidthLength = false
    ): void {
        // Exact width (skip if sqft will override)
        if (!$skipWidthLength && !empty($minProductData['width'])) {
            $varcharData[] = [
                'row_id' => $rowId,
                'attribute_id' => $attributeIds['exact_width'],
                'value' => $minProductId . '_' . $minProductData['width']
            ];
        }

        // Exact length (skip if sqft will override)
        if (!$skipWidthLength && !empty($minProductData['length'])) {
            $varcharData[] = [
                'row_id' => $rowId,
                'attribute_id' => $attributeIds['exact_length'],
                'value' => $minProductId . '_' . $minProductData['length']
            ];
        }

        // Coverage
        if (!empty($minProductData['coverage'])) {
            $varcharData[] = [
                'row_id' => $rowId,
                'attribute_id' => $attributeIds['coverage'],
                'value' => $minProductData['coverage']
            ];

            // Exact height (stores min product id for CASE type)
            $varcharData[] = [
                'row_id' => $rowId,
                'attribute_id' => $attributeIds['exact_height'],
                'value' => $minProductId . '_0'
            ];
        }
    }

    /**
     * Build shipping program attribute based on priority
     *
     * @param array $intData
     * @param int $rowId
     * @param array $simpleProducts
     * @param array $attributeIds
     * @return void
     */
    private function buildShippingProgramAttribute(
        array &$intData,
        int $rowId,
        array $simpleProducts,
        array $attributeIds
    ): void {
        if (!isset($attributeIds['shipping_program'])) {
            return;
        }

        // Collect all shipping programs from simple products
        $shippingPrograms = [];
        foreach ($simpleProducts as $simpleId => $productData) {
            if (!empty($productData['shipping_program'])) {
                $shippingPrograms[] = $productData['shipping_program'];
            }
        }

        if (empty($shippingPrograms)) {
            return;
        }

        // Find highest priority shipping program
        $selectedProgram = $this->selectHighestPriorityShippingProgram($shippingPrograms);

        if ($selectedProgram) {
            $intData[] = [
                'row_id' => $rowId,
                'attribute_id' => $attributeIds['shipping_program'],
                'value' => $selectedProgram
            ];
        }
    }

    /**
     * Select highest priority shipping program from available options
     *
     * @param array $shippingPrograms
     * @return int|null
     */
    private function selectHighestPriorityShippingProgram(array $shippingPrograms): ?int
    {
        foreach (self::SHIPPING_PRIORITY as $priority) {
            foreach ($shippingPrograms as $program) {
                // Program format: "option_id;option_label"
                if (strpos($program, $priority) !== false) {
                    $parts = explode(';', $program);
                    return isset($parts[0]) ? (int)$parts[0] : null;
                }
            }
        }

        // Return first available if no priority match
        if (!empty($shippingPrograms)) {
            $parts = explode(';', $shippingPrograms[0]);
            return isset($parts[0]) ? (int)$parts[0] : null;
        }

        return null;
    }
}

