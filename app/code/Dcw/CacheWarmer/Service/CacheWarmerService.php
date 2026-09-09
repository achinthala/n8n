<?php

declare(strict_types=1);

namespace Dcw\CacheWarmer\Service;

use Amasty\Fpc\Model\Refresher;
use Amasty\Fpc\Model\QueuePageRepository;
use Amasty\Fpc\Model\Queue\PageFactory;
use Amasty\Fpc\Model\ResourceModel\Queue\Page as PageResource;
use Dcw\CacheWarmer\Service\QueueChecker;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Service to warm cache for products and categories after order placement
 */
class CacheWarmerService
{
    /**
     * @var Refresher
     */
    private $refresher;

    /**
     * @var QueueChecker
     */
    private $queueChecker;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var QueuePageRepository
     */
    private $queuePageRepository;

    /**
     * @var PageFactory
     */
    private $queuePageFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Amasty\Fpc\Model\ResourceModel\Queue\Page
     */
    private $pageResource;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param Refresher $refresher
     * @param QueueChecker $queueChecker
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     * @param StockRegistryInterface $stockRegistry
     * @param QueuePageRepository $queuePageRepository
     * @param PageFactory $queuePageFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param PageResource $pageResource
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Refresher $refresher,
        QueueChecker $queueChecker,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection,
        StockRegistryInterface $stockRegistry,
        QueuePageRepository $queuePageRepository,
        PageFactory $queuePageFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        PageResource $pageResource,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->refresher = $refresher;
        $this->queueChecker = $queueChecker;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->stockRegistry = $stockRegistry;
        $this->queuePageRepository = $queuePageRepository;
        $this->queuePageFactory = $queuePageFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->pageResource = $pageResource;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Warm cache for all configurable products in the order
     *
     * @param OrderInterface $order
     * @return void
     */
    public function warmCacheForOrder(OrderInterface $order): void
    {
        $storeId = (int)$order->getStoreId();
        $configurableProductIds = [];
        $categoryIds = [];

        foreach ($order->getAllItems() as $item) {
            // Only process configurable products
            if ($item->getProductType() !== 'configurable') {
                continue;
            }

            $productId = (int)$item->getProductId();
            
            if (!$productId) {
                continue;
            }

            try {
                // Get child items (simple products) for this configurable product
                $children = $item->getChildrenItems();
                
                $product = $this->productRepository->getById($productId, false, $storeId);
                
                // Queue spotlight URL for this configurable product if it exists in CSV
                $this->queueSpotlightUrlForProduct($product, $storeId);

                // Queue spotlight URLs for child simple products
                if ($children && count($children) > 0) {
                    foreach ($children as $childItem) {
                        $childProductId = (int)$childItem->getProductId();
                        if (!$childProductId) {
                            continue;
                        }
                        
                        try {
                            $childProduct = $this->productRepository->getById($childProductId, false, $storeId);
                            $this->queueSpotlightUrlForProduct($childProduct, $storeId);
                        } catch (\Exception $e) {
                            $this->logger->error(
                                'CacheWarmer: Error queuing spotlight URL for child product',
                                ['child_product_id' => $childProductId, 'error' => $e->getMessage()]
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    'CacheWarmer: Error processing product',
                    ['product_id' => $productId, 'error' => $e->getMessage()]
                );
            }
        }
    }

    /**
     * Warm cache for parent configurable product when a product is saved
     *
     * @param Product $product
     * @return void
     */
    public function warmCacheForProduct(Product $product): void
    {
        $productType = $product->getTypeId();
        $storeId = (int)$product->getStoreId();
        
        // If store ID is 0 (admin/default), use null to queue for all stores
        $storeId = $storeId > 0 ? $storeId : null;

        // For simple products, sync manage_stock with incstores_pim_inventory
        if ($productType === 'simple') {
            $this->syncManageStockWithPimInventory($product);
        }

        // Queue spotlight URL for this product (works for all product types)
        $this->queueSpotlightUrlForProduct($product, $storeId);
    }

    /**
     * Sync manage_stock attribute with incstores_pim_inventory attribute
     *
     * @param Product $product
     * @return void
     */
    private function syncManageStockWithPimInventory(Product $product): void
    {
        try {
            // Reload product from default store to ensure all attributes are loaded
            $productId = (int)$product->getId();
            $product = $this->productRepository->getById($productId, false, 0);
            
            // Check if incstores_pim_inventory is set to Yes (true)
            $pimInventoryText = $product->getAttributeText('incstores_pim_inventory');
            $pimInventory = $product->getData('incstores_pim_inventory');
            
            // Handle boolean value - check if it's true, 1, or "Yes"
            $isPimInventoryEnabled = false;
            
            // Check attribute text first (returns "Yes"/"No" for select attributes)
            if ($pimInventoryText) {
                $pimInventoryTextString = (string)$pimInventoryText;
                if (strtolower($pimInventoryTextString) === 'yes' || $pimInventoryTextString === '1') {
                    $isPimInventoryEnabled = true;
                }
            }
            
            // If not enabled via text, check raw value
            if (!$isPimInventoryEnabled) {
                if ($pimInventory === true || $pimInventory === 1 || $pimInventory === '1') {
                    $isPimInventoryEnabled = true;
                } elseif (is_string($pimInventory)) {
                    $isPimInventoryEnabled = (strtolower($pimInventory) === 'yes' || strtolower($pimInventory) === 'true');
                }
            }
            
            // Set manage_stock based on incstores_pim_inventory
            $manageStockValue = $isPimInventoryEnabled ? 1 : 0;
            
            // Get stock item and update only manage_stock
            // Use the same approach as Magento's SaveInventoryDataObserver
            $stockItem = $this->stockRegistry->getStockItem($productId);
            
            if (!$stockItem || !$stockItem->getItemId()) {
                $this->logger->error(
                    'CacheWarmer: Stock item not found for product',
                    ['product_id' => $productId, 'sku' => $product->getSku()]
                );
                return;
            }
            
            $currentManageStock = (int)$stockItem->getManageStock();
            
            // Only update if the value is different
            if ($currentManageStock != $manageStockValue) {
                // Always uncheck "Use Config Settings" when setting a specific value
                // This is the Magento-recommended approach (same pattern as UpdateBackorderStatus observer)
                // When you set a specific manage_stock value, the product must use its own value, not config
                $stockItem->setUseConfigManageStock(false);
                $stockItem->setManageStock($manageStockValue);
                $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error syncing manage_stock with incstores_pim_inventory',
                [
                    'product_id' => $product->getId(),
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Queue spotlight URL for a specific product if it exists in CSV
     *
     * @param Product $product
     * @param int|null $storeId
     * @return void
     */
    private function queueSpotlightUrlForProduct(Product $product, ?int $storeId): void
    {
        try {
            $productSku = $product->getSku();
            
            if (!$productSku) {
                return;
            }

            // If storeId is not provided, use default store
            if ($storeId === null) {
                $storeId = (int)$this->storeManager->getStore()->getId();
            }

            // Get spotlight URL from CSV for this SKU
            $spotlightUrl = $this->getSpotlightUrlFromCsv($productSku);
            
            if (!$spotlightUrl) {
                return;
            }

            // Check if URL is already pending in queue (use full URL for checking)
            if ($this->queueChecker->isSpotlightUrlPending($spotlightUrl, $storeId)) {
                return;
            }

            // Queue the full URL
            $this->addUrlToQueue($spotlightUrl, $storeId);
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error queuing spotlight URL for product',
                [
                    'product_id' => $product->getId(),
                    'sku' => $product->getSku(),
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Get spotlight URL from CSV file for a given SKU
     *
     * @param string $sku
     * @return string|null
     */
    private function getSpotlightUrlFromCsv(string $sku): ?string
    {
        $mediapath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $filePath = $mediapath . '/import/supplemental_feed.csv';

        if (!file_exists($filePath)) {
            return null;
        }

        if (($handle = fopen($filePath, 'r')) === false) {
            return null;
        }

        // Skip header row
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 4) {
                $csvSku = trim($data[0]); // id column (SKU)
                $link = trim($data[3]); // link column (spotlight URL)
                
                if ($csvSku === $sku && !empty($link)) {
                    fclose($handle);
                    return $link;
                }
            }
        }
        
        fclose($handle);
        return null;
    }

    private function addUrlToQueue(string $spotlightUrl, int $storeId): void
    {
        // Get existing page or create new one (same pattern as Refresher)
        // Use full spotlightUrl for lookup and storage
        $queuePage = $this->queuePageRepository->getByUrl($spotlightUrl, $storeId);
        
        // If page already exists, just update the rate (no need to add duplicate)
        if ($queuePage->getId()) {
            $rate = (int)$this->pageResource->getMaxRate() + 1;
            $queuePage->setRate($rate);
            $this->queuePageRepository->save($queuePage);
            return;
        }
        
        // Page doesn't exist, create new one with full URL
        $queuePage->setUrl($spotlightUrl);
        $queuePage->setStore($storeId);
        
        // Set rate (get max rate + 1, same as Refresher)
        $rate = (int)$this->pageResource->getMaxRate() + 1;
        $queuePage->setRate($rate);
        
        $this->queuePageRepository->save($queuePage);
    }

    /**
     * Generate warmerurls.txt file with all active configurable product URLs
     *
     * @param int|null $storeId
     * @return array Returns array with 'count' and 'file_path'
     * @throws \Exception
     */
    public function generateWarmerUrlsFile(?int $storeId = null): array
    {
        try {
            // Use default store if not provided
            if ($storeId === null) {
                $store = $this->storeManager->getStore();
                $storeId = (int)$store->getId();
            } else {
                $store = $this->storeManager->getStore($storeId);
            }

            // Create export directory if it doesn't exist
            $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $exportDir = $varDir->getAbsolutePath('export');
            
            if (!$varDir->isDirectory('export')) {
                $varDir->create('export');
            }

            $filePath = $exportDir . '/warmerurls.txt';

            // Open file handle for writing
            $handle = fopen($filePath, 'w');
            if (!$handle) {
                throw new \RuntimeException('Unable to create warmerurls.txt file');
            }

            $count = 0;

            // Get all active configurable products
            $productCollection = $this->productCollectionFactory->create()
                ->addAttributeToSelect(['url_key'])
                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToFilter('status', 1) // Active products only
                ->addAttributeToFilter('visibility', ['neq' => 1]) // Not "Not Visible"
                ->setStoreId($storeId);

            foreach ($productCollection as $product) {
                if (!$product->getUrlKey()) {
                    continue;
                }

                try {
                    $url = $product->getUrlModel()->getUrl($product);
                    if ($url) {
                        fwrite($handle, $url . PHP_EOL);
                        $count++;
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        'CacheWarmer: Error generating URL for product',
                        [
                            'product_id' => $product->getId(),
                            'sku' => $product->getSku(),
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }

            fclose($handle);

            return [
                'count' => $count,
                'file_path' => $filePath
            ];
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error generating warmerurls.txt file',
                [
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]
            );
            throw $e;
        }
    }

    /**
     * Clear queue while preserving URLs that contain "spotlight" parameter
     *
     * @return int Number of spotlight URLs preserved
     * @throws \Exception
     */
    public function clearQueuePreservingSpotlight(): int
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('amasty_fpc_queue_page');
            
            // Get all URLs that contain "spotlight" in the URL
            $select = $connection->select()
                ->from($tableName, ['id', 'url', 'store', 'rate', 'activity_id'])
                ->where('url LIKE ?', '%spotlight%');
            
            $spotlightPages = $connection->fetchAll($select);
            $preservedCount = count($spotlightPages);
            
            if ($preservedCount > 0) {
                $this->logger->info(
                    "[CacheWarmer] Preserving {$preservedCount} spotlight URLs before clearing queue"
                );
            }
            
            // Clear the entire queue (truncate table)
            $this->queuePageRepository->clear();
            
            // Re-add spotlight URLs back to the queue
            if ($preservedCount > 0) {
                $maxRate = (int)$this->pageResource->getMaxRate();
                
                foreach ($spotlightPages as $pageData) {
                    try {
                        $queuePage = $this->queuePageFactory->create();
                        $queuePage->setUrl($pageData['url']);
                        $queuePage->setStore((int)$pageData['store']);
                        $queuePage->setRate($maxRate + 1);
                        
                        // Preserve activity_id if it exists
                        if (!empty($pageData['activity_id'])) {
                            $queuePage->setActivityId((int)$pageData['activity_id']);
                        }
                        
                        $this->queuePageRepository->save($queuePage);
                        $maxRate++;
                    } catch (\Exception $e) {
                        $this->logger->error(
                            '[CacheWarmer] Error re-adding spotlight URL to queue',
                            [
                                'url' => $pageData['url'] ?? 'unknown',
                                'error' => $e->getMessage()
                            ]
                        );
                    }
                }
                
                $this->logger->info(
                    "[CacheWarmer] Successfully preserved and re-added {$preservedCount} spotlight URLs"
                );
            }
            
            return $preservedCount;
        } catch (\Exception $e) {
            $this->logger->error(
                '[CacheWarmer] Error clearing queue while preserving spotlight URLs: ' . $e->getMessage(),
                ['exception' => $e]
            );
            throw $e;
        }
    }
}
