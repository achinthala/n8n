<?php
/**
 * Copyright © Dcw. All rights reserved.
 * Service class for calculating minimum prices from simple products
 */
declare(strict_types=1);

namespace Dcw\MinimumPriceCron\Model;

use Dcw\MinimumPriceCron\Model\ResourceModel\ProductData;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Psr\Log\LoggerInterface;

/**
 * Class ProductPriceCalculator
 * 
 * Calculates minimum prices and identifies minimum-priced simple products for configurables
 */
class ProductPriceCalculator
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ProductData
     */
    private ProductData $productDataResource;

    /**
     * @var ProductSalabilityChecker
     */
    private ProductSalabilityChecker $salabilityChecker;

    /**
     * @param LoggerInterface $logger
     * @param ProductData $productDataResource
     * @param ProductSalabilityChecker $salabilityChecker
     */
    public function __construct(
        LoggerInterface $logger,
        ProductData $productDataResource,
        ProductSalabilityChecker $salabilityChecker
    ) {
        $this->logger = $logger;
        $this->productDataResource = $productDataResource;
        $this->salabilityChecker = $salabilityChecker;
    }

    /**
     * Get configurable products with calculated prices
     *
     * @param array $productRowIds
     * @param array $attributeIds
     * @return array
     */
    public function getConfigurableProductsWithPrices(array $productRowIds, array $attributeIds): array
    {
        // Get all simple products associated with configurables
        $simpleProducts = $this->productDataResource->getSimpleProductData($productRowIds, $attributeIds);

        // Get stock status for all simple products
        $stockStatus = $this->productDataResource->getStockStatus($productRowIds);

        // Get product status (enabled/disabled)
        $productStatus = $this->productDataResource->getProductStatus($productRowIds);

        // Get special prices
        $specialPrices = $this->productDataResource->getSpecialPrices($productRowIds);

        // Get tier prices
        $tierPrices = $this->productDataResource->getTierPrices($productRowIds);

        // Calculate effective prices and group by configurable
        $configurableData = [];

        foreach ($simpleProducts as $product) {
            $simpleId = (int)$product['simple_id'];
            $configurableRowId = (int)$product['configurable_row_id'];
            $basePrice = (float)$product['price'];

            // Check if product is salable
            if (!$this->salabilityChecker->isSalable($simpleId, $stockStatus, $productStatus)) {
                continue;
            }

            // Calculate effective price (considering special price and tier price)
            $effectivePrice = $this->calculateEffectivePrice(
                $simpleId,
                $basePrice,
                $specialPrices,
                $tierPrices
            );

            // Store product data grouped by configurable
            if (!isset($configurableData[$configurableRowId])) {
                $configurableData[$configurableRowId] = [
                    'simple_products' => [],
                    'configurable_id' => $product['configurable_id']
                ];
            }

            $configurableData[$configurableRowId]['simple_products'][$simpleId] = [
                'price' => $effectivePrice,
                'sku' => $product['simple_sku'],
                'width' => $product['exact_width'] ?? null,
                'length' => $product['exact_length'] ?? null,
                'coverage' => $product['coverage'] ?? null,
                'shipping_program' => $product['shipping_program'] ?? null,
                'calculator_type' => $product['calculator_type'] ?? null
            ];
        }

        return $configurableData;
    }

    /**
     * Calculate effective price considering special price and tier price
     *
     * @param int $simpleId
     * @param float $basePrice
     * @param array $specialPrices
     * @param array $tierPrices
     * @return float
     */
    private function calculateEffectivePrice(
        int $simpleId,
        float $basePrice,
        array $specialPrices,
        array $tierPrices
    ): float {
        $prices = [$basePrice];

        // Add special price if exists and is lower
        if (isset($specialPrices[$simpleId]) && $specialPrices[$simpleId] > 0) {
            $prices[] = (float)$specialPrices[$simpleId];
        }

        // Add tier price if exists and is lower
        if (isset($tierPrices[$simpleId]) && $tierPrices[$simpleId] > 0) {
            $prices[] = (float)$tierPrices[$simpleId];
        }

        return min($prices);
    }
}

