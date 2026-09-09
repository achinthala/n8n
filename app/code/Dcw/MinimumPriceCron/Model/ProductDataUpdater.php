<?php
/**
 * Copyright © Dcw. All rights reserved.
 * Service class for updating configurable product data based on minimum priced simple products
 */
declare(strict_types=1);

namespace Dcw\MinimumPriceCron\Model;

use Dcw\MinimumPriceCron\Model\ResourceModel\ProductData;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class ProductDataUpdater
 * 
 * Handles updating configurable products with minimum priced simple product data
 */
class ProductDataUpdater
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
     * @var ProductPriceCalculator
     */
    private ProductPriceCalculator $priceCalculator;

    /**
     * @var AttributeDataBuilder
     */
    private AttributeDataBuilder $attributeDataBuilder;

    /**
     * @param LoggerInterface $logger
     * @param ProductData $productDataResource
     * @param ProductPriceCalculator $priceCalculator
     * @param AttributeDataBuilder $attributeDataBuilder
     */
    public function __construct(
        LoggerInterface $logger,
        ProductData $productDataResource,
        ProductPriceCalculator $priceCalculator,
        AttributeDataBuilder $attributeDataBuilder
    ) {
        $this->logger = $logger;
        $this->productDataResource = $productDataResource;
        $this->priceCalculator = $priceCalculator;
        $this->attributeDataBuilder = $attributeDataBuilder;
    }

    /**
     * Update configurable products with minimum priced simple product data
     *
     * @return int Number of products updated
     * @throws LocalizedException
     */
    public function updateConfigurableProducts(): int
    {
        $updatedCount = 0;

        try {
            // Get products updated in the last hour (including their parent configurables)
            $productRowIds = $this->productDataResource->getRecentlyUpdatedProductRowIds();

            if (empty($productRowIds)) {
                $this->logger->info('No recently updated products found');
                return 0;
            }

            $this->logger->info('Processing products', ['count' => count($productRowIds)]);

            // Get all attribute IDs needed
            $attributeIds = $this->productDataResource->getRequiredAttributeIds();

            // Get configurable products with their simple products and prices
            $configurableData = $this->priceCalculator->getConfigurableProductsWithPrices(
                $productRowIds,
                $attributeIds
            );

            if (empty($configurableData)) {
                $this->logger->info('No configurable products to update');
                return 0;
            }

            // Build attribute data for each configurable product based on minimum priced simple
            $attributeDataToInsert = $this->attributeDataBuilder->buildAttributeData(
                $configurableData,
                $attributeIds
            );

            // Bulk insert/update attribute data
            if (!empty($attributeDataToInsert['varchar'])) {
                $this->productDataResource->bulkUpdateVarcharAttributes($attributeDataToInsert['varchar']);
                $updatedCount += count($attributeDataToInsert['varchar']);
            }

            if (!empty($attributeDataToInsert['int'])) {
                $this->productDataResource->bulkUpdateIntAttributes($attributeDataToInsert['int']);
                $updatedCount += count($attributeDataToInsert['int']);
            }

            $this->logger->info('Successfully updated product attributes', ['updated_count' => $updatedCount]);

        } catch (\Exception $e) {
            $this->logger->error(
                'Error updating configurable products',
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            throw new LocalizedException(__('Failed to update configurable products: %1', $e->getMessage()));
        }

        return $updatedCount;
    }
}

