<?php
/**
 * Simple Product Revenue Controller
 */

namespace Dcw\RevenueRanking\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Logger\Monolog;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Framework\View\LayoutInterface;
use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking\CollectionFactory as RevenueRankingCollectionFactory;

class ProductRevenue implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Monolog
     */
    private $logger;

    /**
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var RevenueRankingCollectionFactory
     */
    private $revenueRankingCollectionFactory;

    /**
     * Constructor
     */
    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        Monolog $logger,
        Resolver $layerResolver,
        CollectionFactory $productCollectionFactory,
        LayoutInterface $layout,
        RevenueRankingCollectionFactory $revenueRankingCollectionFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->layerResolver = $layerResolver;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->layout = $layout;
        $this->revenueRankingCollectionFactory = $revenueRankingCollectionFactory;
    }

    /**
     * Execute AJAX request
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            
            // Get action from request
            $action = $this->request->getParam('action', '');
            
            if ($action === 'get_product_data') {
                // Get product IDs from frontend (the actual products displayed on the page)
                $currentPageProductIdsParam = $this->request->getParam('current_page_product_ids', '');
                $currentUrl = $this->request->getParam('current_url', '');
                $toolbarCount = $this->request->getParam('toolbar_count', 0);
                
                // Try to get the actual filtered products from Mirasvit collection
                $mirasvitCollection = $this->layerResolver->get()->getProductCollection();
                $mirasvitCollection->setPageSize(false); // Get all products
                $mirasvitCollection->setCurPage(1);
                $allMirasvitProductIds = $mirasvitCollection->getAllIds();
                
                // If Mirasvit collection returns a reasonable number (not all 26,450), use it
                if (count($allMirasvitProductIds) <= 1000 && count($allMirasvitProductIds) >= $toolbarCount) {
                    // Use the first N products from Mirasvit collection to match toolbar count
                    $mirasvitProductIds = array_slice($allMirasvitProductIds, 0, $toolbarCount);
                    $mirasvitProductCount = $toolbarCount;
                } else {
                    // Fallback: use frontend product IDs but limit to toolbar count
                    $mirasvitProductCount = $toolbarCount;
                    
                    if (!empty($currentPageProductIdsParam)) {
                        $frontendProductIds = array_map('intval', explode(',', $currentPageProductIdsParam));
                        // Limit frontend IDs to toolbar count
                        $mirasvitProductIds = array_slice($frontendProductIds, 0, $toolbarCount);
                    } else {
                        // Last resort: use layer collection but limit to toolbar count
                        $layer = $this->layerResolver->get();
                        $layerCollection = $layer->getProductCollection();
                        $layerCollection->setPageSize($toolbarCount);
                        $layerCollection->setCurPage(1);
                        $mirasvitProductIds = $layerCollection->getAllIds();
                    }
                }
                
                // Calculate Buy % for each product
                $buyPercentages = [];
                $totalFilteredRevenue = 0;
                $missingRevenueProducts = [];
                
                if (!empty($mirasvitProductIds)) {
                    // Get revenue data for all filtered products
                    $revenueCollection = $this->revenueRankingCollectionFactory->create();
                    $revenueCollection->addFieldToFilter('product_id', ['in' => $mirasvitProductIds]);
                    
                    $revenueData = [];
                    foreach ($revenueCollection as $revenueItem) {
                        $revenueData[$revenueItem->getProductId()] = $revenueItem->getAdjustedRevenue();
                    }
                    
                    // Calculate total revenue for all filtered products
                    foreach ($mirasvitProductIds as $productId) {
                        $productRevenue = isset($revenueData[$productId]) ? (float)$revenueData[$productId] : 0;
                        $totalFilteredRevenue += $productRevenue;
                        
                        // Track products missing revenue data
                        if (!isset($revenueData[$productId])) {
                            $missingRevenueProducts[] = $productId;
                        }
                    }
                    
                    // Calculate Buy % for each product
                    foreach ($mirasvitProductIds as $productId) {
                        $productRevenue = isset($revenueData[$productId]) ? (float)$revenueData[$productId] : 0;
                        $buyPercentage = $totalFilteredRevenue > 0 ? ($productRevenue / $totalFilteredRevenue) * 100 : 0;
                        
                        $buyPercentages[] = [
                            'product_id' => $productId,
                            'revenue' => $productRevenue,
                            'buy_percentage' => round($buyPercentage, 2),
                            'has_revenue_data' => isset($revenueData[$productId])
                        ];
                    }
                }
                
                // No need to add placeholders since we're limiting to toolbar count
                
                // Get total products in category (before filters) for comparison
                $totalCollection = $this->productCollectionFactory->create();
                $totalCollection->addAttributeToFilter('status', 1); // Enabled products
                $totalCollection->addAttributeToFilter('visibility', [1, 2, 3, 4]); // Visible products
                $totalProducts = $totalCollection->getSize();
                
                $response = [
                    'success' => true,
                    'total_products' => $totalProducts,
                    'mirasvit_products' => count($buyPercentages), // Use actual count of all products in calculation
                    'mirasvit_collection_count' => $mirasvitProductCount, // Keep original for reference
                    'toolbar_count' => $toolbarCount,
                    'product_ids' => implode(', ', array_column($buyPercentages, 'product_id')), // Show ALL product IDs
                    'all_product_ids_count' => count($buyPercentages),
                    'total_filtered_revenue' => $totalFilteredRevenue,
                    'buy_percentages' => $buyPercentages,
                    'missing_revenue_products' => $missingRevenueProducts,
                    'missing_revenue_count' => count($missingRevenueProducts),
                    'products_with_revenue' => count($buyPercentages) - count($missingRevenueProducts),
                    'message' => 'Mirasvit product data and Buy % calculated successfully',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
            } else {
                // Default response
                $response = [
                    'success' => true,
                    'message' => 'Simple AJAX working - no action specified',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            
        } catch (\Exception $e) {
            
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        return $result->setData($response);
    }
}
