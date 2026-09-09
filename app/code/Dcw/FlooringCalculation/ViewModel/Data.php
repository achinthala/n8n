<?php

declare(strict_types=1);

namespace Dcw\FlooringCalculation\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class Data implements ArgumentInterface
{
    /**
     * @var Http
     */
    protected $request;
    /**
     * @var Cart
     */
    protected $cart;
    /**
     * @var PricingHelper
     */
    protected $pricingHelper;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    public function __construct(
        Http $request,
        Cart $cart,
        PricingHelper $pricingHelper,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->request = $request;
        $this->cart = $cart;
        $this->pricingHelper = $pricingHelper;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->resourceConnection = $resourceConnection;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getCart()
    {
        return $this->cart;
    }

    public function getPricingHelper()
    {
        return $this->pricingHelper;
    }

    /**
     * Check if product can be added to cart with specified quantity
     * Uses Magento's default add to cart validation logic
     * 
     * @param int $productId
     * @param float $qty
     * @return bool
     */
    public function isProductCanAddToCart($productId, $qty)
    {
        try {
            // Load the product
            $product = $this->productRepository->getById($productId);
            
            // Check if product exists and is available
            if (!$product || !$product->getId()) {
                return false;
            }
            
            // Check if product is enabled
            if ($product->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                return false;
            }
            
            // For configurable products, check if it has options
            if ($product->getTypeId() == 'configurable') {
                $configurableProduct = $product->getTypeInstance();
                if (!$configurableProduct->hasOptions($product)) {
                    return false;
                }
            }
            
            // Use getProductAvailableQty for stock validation
            $availableQty = $this->getProductAvailableQty($productId);
            
            // If available quantity is 0, product is out of stock
            if ($availableQty == 0) {
                return false;
            }
            
            // Check if backorder is allowed
            $stockItem = $this->stockRegistry->getStockItem($productId);
            if ($stockItem && $stockItem->getManageStock() && !$stockItem->getBackorders()) {
                // Backorder not allowed - check quantity
                if ($availableQty < $qty) {
                    return false;
                }
            }
            // If backorder is allowed or stock management disabled, quantity check is bypassed
            
            // Additional validation for simple products
            if ($product->getTypeId() == 'simple') {
                // Check if product has required options
                if ($product->getRequiredOptions() && !$product->getHasOptions()) {
                    return false;
                }
            }
            
            // Check if product can be purchased
            if (!$product->isAvailable()) {
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available quantity for a product
     * Optimized version that accepts product object to avoid reloading
     * 
     * @param int|Product $productIdOrProduct Product ID or Product object
     * @return float
     */
    public function getProductAvailableQty($productIdOrProduct)
    {
        try {
            $productId = is_object($productIdOrProduct) ? $productIdOrProduct->getId() : $productIdOrProduct;
            
            if (!$productId) {
                return 0;
            }
            
            // Get stock item for the product
            $stockItem = $this->stockRegistry->getStockItem($productId);
            
            // If stock management is not enabled, return a high number to indicate unlimited
            if (!$stockItem->getManageStock()) {
                return 99999; // High number indicates unlimited stock
            }
            
            // If product is not in stock, return 0
            if (!$stockItem->getIsInStock()) {
                return 0;
            }
            
            // If backorder is allowed, return a high number to indicate unlimited
            if ($stockItem->getBackorders()) {
                return 99999; // High number indicates unlimited stock
            }
            
            // Use getQty() directly - stockState->getStockQty() returns 0 when manage_stock=0,
            // is_in_stock=0, or product not saleable, even when cataloginventory has qty
            return (float) $stockItem->getQty();
            
        } catch (\Exception $e) {
            // Log the error if needed
            return 0;
        }
    }

    /**
     * Batch get available quantities for multiple products
     * Optimized to use a single database query instead of 316 individual calls
     * 
     * @param array $productIds Array of product IDs
     * @return array Array of product_id => available_quantity
     */
    public function getProductsAvailableQtyBatch(array $productIds): array
    {
        $result = [];
        
        if (empty($productIds)) {
            return $result;
        }
        
        // Initialize all results to 0
        foreach ($productIds as $productId) {
            $result[$productId] = 0;
        }
        
        try {
            // Use direct database query for maximum performance
            // This replaces individual method calls with 1 query
            $connection = $this->resourceConnection->getConnection();
            $stockItemTable = $connection->getTableName('cataloginventory_stock_item');
            $stockStatusTable = $connection->getTableName('cataloginventory_stock_status');
            
            // Get all stock data in a single query
            $select = $connection->select()
                ->from(['si' => $stockItemTable], [
                    'product_id',
                    'manage_stock',
                    'is_in_stock',
                    'qty',
                    'backorders'
                ])
                ->joinLeft(
                    ['ss' => $stockStatusTable],
                    'si.product_id = ss.product_id AND si.stock_id = ss.stock_id',
                    ['qty as status_qty']
                )
                ->where('si.product_id IN (?)', $productIds)
                ->where('si.stock_id = ?', 1); // Default stock
            
            $stockData = $connection->fetchAll($select);
            
            // Process the batch data
            foreach ($stockData as $row) {
                $productId = (int)$row['product_id'];
                
                // If stock management is not enabled, return a high number to indicate unlimited
                if (!$row['manage_stock']) {
                    $result[$productId] = 99999;
                    continue;
                }
                
                // If product is not in stock, return 0
                if (!$row['is_in_stock']) {
                    $result[$productId] = 0;
                    continue;
                }
                
                // Get the available quantity
                $availableQty = (float)($row['qty'] ?? 0);
                
                // If backorder is allowed, return a high number
                if ($row['backorders']) {
                    $result[$productId] = 99999;
                } else {
                    $result[$productId] = $availableQty;
                }
            }
            
        } catch (\Exception $e) {
            // If batch query fails, fall back to individual calls
            foreach ($productIds as $productId) {
                $result[$productId] = $this->getProductAvailableQty($productId);
            }
        }
        
        return $result;
    }

    /**
     * Batch get stock items for multiple products
     * Optimized to use a single database query instead of individual calls
     * Returns both raw stock item data and calculated available quantities
     * 
     * @param array $productIds Array of product IDs
     * @return array Array with 'stock_items' and 'available_quantities' keys
     *               stock_items: product_id => stock_item_data (qty, is_in_stock, manage_stock, backorders)
     *               available_quantities: product_id => calculated available quantity
     */
    public function getProductsStockItemsBatch(array $productIds): array
    {
        $stockItems = [];
        $availableQuantities = [];
        
        if (empty($productIds)) {
            return ['stock_items' => $stockItems, 'available_quantities' => $availableQuantities];
        }
        
        // Initialize all results with default values
        foreach ($productIds as $productId) {
            $stockItems[$productId] = [
                'qty' => 0,
                'is_in_stock' => false,
                'manage_stock' => true,
                'backorders' => false
            ];
            $availableQuantities[$productId] = 0;
        }
        
        try {
            // Use direct database query for maximum performance
            $connection = $this->resourceConnection->getConnection();
            $stockItemTable = $connection->getTableName('cataloginventory_stock_item');
            
            // Get all stock item data in a single query
            $select = $connection->select()
                ->from(['si' => $stockItemTable], [
                    'product_id',
                    'qty',
                    'is_in_stock',
                    'manage_stock',
                    'backorders'
                ])
                ->where('si.product_id IN (?)', $productIds)
                ->where('si.stock_id = ?', 1); // Default stock
            
            $stockData = $connection->fetchAll($select);
            
            // Process the batch data
            foreach ($stockData as $row) {
                $productId = (int)$row['product_id'];
                $qty = (float)($row['qty'] ?? 0);
                $isInStock = (bool)$row['is_in_stock'];
                $manageStock = (bool)$row['manage_stock'];
                $backorders = (bool)$row['backorders'];
                
                $stockItems[$productId] = [
                    'qty' => $qty,
                    'is_in_stock' => $isInStock,
                    'manage_stock' => $manageStock,
                    'backorders' => $backorders
                ];
                
                // Calculate available quantity using same logic as getProductsAvailableQtyBatch
                if (!$manageStock) {
                    $availableQuantities[$productId] = 99999;
                } elseif (!$isInStock) {
                    $availableQuantities[$productId] = 0;
                } elseif ($backorders) {
                    $availableQuantities[$productId] = 99999;
                } else {
                    $availableQuantities[$productId] = $qty;
                }
            }
            
        } catch (\Exception $e) {
            // If batch query fails, fall back to individual calls
            foreach ($productIds as $productId) {
                try {
                    $stockItem = $this->stockRegistry->getStockItem($productId);
                    $qty = (float)$stockItem->getQty();
                    $isInStock = (bool)$stockItem->getIsInStock();
                    $manageStock = (bool)$stockItem->getManageStock();
                    $backorders = (bool)$stockItem->getBackorders();
                    
                    $stockItems[$productId] = [
                        'qty' => $qty,
                        'is_in_stock' => $isInStock,
                        'manage_stock' => $manageStock,
                        'backorders' => $backorders
                    ];
                    
                    // Calculate available quantity
                    if (!$manageStock) {
                        $availableQuantities[$productId] = 99999;
                    } elseif (!$isInStock) {
                        $availableQuantities[$productId] = 0;
                    } elseif ($backorders) {
                        $availableQuantities[$productId] = 99999;
                    } else {
                        $availableQuantities[$productId] = $qty;
                    }
                } catch (\Exception $e2) {
                    // Keep default values already set
                }
            }
        }
        
        return [
            'stock_items' => $stockItems,
            'available_quantities' => $availableQuantities
        ];
    }

    /**
     * Get the current available quantity for a product
     * 
     * Uses stockRegistry->getStockItem()->getQty() instead of stockState->getStockQty()
     * because getStockQty() returns 0 when manage_stock=0, is_in_stock=0, or product
     * is not saleable - even when cataloginventory_stock_item.qty has a value.
     * 
     * @param int $productId
     * @return float
     */
    public function getProductCurrentAvailableQty($productId)
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($productId);
            if (!$stockItem->getManageStock()) {
                return 99999; // Unlimited when stock not managed
            }
            if (!$stockItem->getIsInStock()) {
                return 0;
            }
            return (float) $stockItem->getQty();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get the calculated quantity for a product in the mini cart
     * 
     * This method retrieves a product from the current cart by SKU and calculates
     * the total quantity by multiplying the cart item quantity with the roll length
     * from the product's PDP line item data.
     * 
     * @param string $sku The SKU of the product to find in the cart
     * @return float The calculated quantity (cart qty * roll length) or 0 if not found
     */
    public function getMiniCartQty($sku)
    {
        try {
            $quote = $this->cart->getQuote();

            if (!$quote || !$quote->getId()) {
                return 0;
            }

            $items = $quote->getAllItems();
            
            if (empty($items)) {
                return 0;
            }

            $itemTotalQty = 0;

            foreach ($items as $item) {
                if ($item->getSku() == $sku && $item->getProductType() == 'configurable') {
                    $getPdpLineItem = $item->getPdpLineItem();
                    if ($getPdpLineItem) {
                        $data = json_decode($getPdpLineItem, true);
                        
                        $rollLength = 1;

                        if (isset($data['RoomLength'])) {
                            $rollLength = $data['RoomLength'];
                        }

                        $calculatedQty = (float)$item->getQty() * (float)$rollLength;
                        $itemTotalQty = $itemTotalQty + $calculatedQty;
                    }
                }
            }

            return $itemTotalQty;
        } catch (\Exception $e) {
            $this->logger->error('getMiniCartQty - Exception occurred: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Load a product by SKU
     * 
     * @param string $sku
     * @return Product|false
     */
    public function loadProductBySku($sku)
    {
        try {
            return $this->productRepository->get($sku);
        } catch (\Exception $e) {
            $this->logger->error('loadProductBySku - Exception occurred: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Load a quote by ID and return its items
     * 
     * @param int $quoteId
     * @return \Magento\Quote\Api\Data\CartItemInterface[]|false
     */
    public function loadQuoteById($quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            return $quote->getAllItems();
        } catch (\Exception $e) {
            $this->logger->error('loadQuoteById - Exception occurred: ' . $e->getMessage());
            return false;
        }
    }
}