<?php
/**
 * Copyright © Dcw. All rights reserved.
 * Service class for managing stock status
 */
declare(strict_types=1);

namespace Dcw\MinimumPriceCron\Model;

use Dcw\MinimumPriceCron\Model\ResourceModel\ProductData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Class StockManager
 * 
 * Manages stock status for products with special out-of-stock visibility rules
 */
class StockManager
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
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @param LoggerInterface $logger
     * @param ProductData $productDataResource
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        LoggerInterface $logger,
        ProductData $productDataResource,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry
    ) {
        $this->logger = $logger;
        $this->productDataResource = $productDataResource;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Update stock status for recently modified products
     *
     * @return int Number of products updated
     */
    public function updateStockForModifiedProducts(): int
    {
        $updatedCount = 0;

        try {
            $productIds = $this->productDataResource->getRecentlyUpdatedSimpleProductIds();

            if (empty($productIds)) {
                $this->logger->info('No recently updated simple products found for stock update');
                return 0;
            }

            $this->logger->info('Processing stock updates', ['product_count' => count($productIds)]);

            foreach ($productIds as $productId) {
                try {
                    if ($this->updateProductStock((int)$productId)) {
                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Failed to update stock for product',
                        [
                            'product_id' => $productId,
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }

            $this->logger->info('Stock updates completed', ['updated_count' => $updatedCount]);

        } catch (\Exception $e) {
            $this->logger->error(
                'Error updating stock for modified products',
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        }

        return $updatedCount;
    }

    /**
     * Update stock status for a single product
     *
     * @param int $productId
     * @return bool True if updated, false otherwise
     */
    private function updateProductStock(int $productId): bool
    {
        try {
            $product = $this->productRepository->getById($productId);

            // Check if product has special OOS visibility flag
            $visibleWhenOos = $product->getData('incstores_pim_visible_when_oos');

            if (!$visibleWhenOos) {
                return false;
            }

            // Get stock item
            $stockItem = $this->stockRegistry->getStockItem($productId);

            if (!$stockItem) {
                $this->logger->warning(
                    'Stock item not found for product',
                    ['product_id' => $productId]
                );
                return false;
            }

            // Update stock settings to allow backorders and keep product visible
            $stockItem->setIsInStock(true);
            $stockItem->setBackorders(2); // Allow with notification
            $stockItem->setManageStock(true);

            // Save using stock registry
            $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);

            $this->logger->info(
                'Product stock updated successfully',
                [
                    'product_id' => $productId,
                    'sku' => $product->getSku()
                ]
            );

            return true;

        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                'Product not found during stock update',
                ['product_id' => $productId, 'error' => $e->getMessage()]
            );
            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error updating product stock',
                [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
            return false;
        }
    }
}

