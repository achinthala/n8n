<?php
/**
 * Copyright © Dcw. All rights reserved.
 * Service class for checking product salability
 */
declare(strict_types=1);

namespace Dcw\MinimumPriceCron\Model;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Class ProductSalabilityChecker
 * 
 * Determines if a product is salable based on status, stock, and backorder settings
 */
class ProductSalabilityChecker
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @param LoggerInterface $logger
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        LoggerInterface $logger,
        StockRegistryInterface $stockRegistry
    ) {
        $this->logger = $logger;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Check if product is salable based on stock and status
     *
     * @param int $productId
     * @param array $stockStatus
     * @param array $productStatus
     * @return bool
     */
    public function isSalable(int $productId, array $stockStatus, array $productStatus): bool
    {
        // Check if product is enabled
        if (!isset($productStatus[$productId]) || (int)$productStatus[$productId] !== Status::STATUS_ENABLED) {
            return false;
        }

        // Check if product is in stock
        if (!isset($stockStatus[$productId]) || (int)$stockStatus[$productId] !== 1) {
            return false;
        }

        try {
            // Additional check using stock registry for backorders
            $stockItem = $this->stockRegistry->getStockItem($productId);
            
            if (!$stockItem) {
                return false;
            }

            $isInStock = $stockItem->getIsInStock();
            $qty = $stockItem->getQty();
            $backorders = $stockItem->getBackorders();

            // Product is salable if in stock, or if out of stock but backorders enabled
            if ($isInStock) {
                return true;
            }

            if ($qty <= 0 && $backorders > 0) {
                return true;
            }

            return false;

        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                'Product not found during salability check',
                ['product_id' => $productId, 'error' => $e->getMessage()]
            );
            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error checking product salability',
                ['product_id' => $productId, 'error' => $e->getMessage()]
            );
            return false;
        }
    }
}

