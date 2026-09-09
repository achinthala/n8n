<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Product\Visibility;
use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking;
use Magento\Store\Model\StoreManagerInterface;

class RevenueRankingViewModel implements ArgumentInterface
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RevenueRanking
     */
    private $revenueRankingResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Visibility
     */
    private $visibility;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        Resolver $layerResolver,
        Registry $registry,
        RequestInterface $request,
        RevenueRanking $revenueRankingResource,
        StoreManagerInterface $storeManager,
        Visibility $visibility
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->layerResolver = $layerResolver;
        $this->registry = $registry;
        $this->request = $request;
        $this->revenueRankingResource = $revenueRankingResource;
        $this->storeManager = $storeManager;
        $this->visibility = $visibility;
    }

    /**
     * Get current store ID
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * Get product count by multiple filters
     *
     * @param array $filters
     * @return int
     */
    public function getProductCountByFilters(array $filters = [])
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
            $collection->addAttributeToFilter('visibility', ['in' => $this->visibility->getVisibleInSiteIds()]);

            // Apply filters
            foreach ($filters as $attribute => $value) {
                if (is_array($value)) {
                    $collection->addAttributeToFilter($attribute, ['in' => $value]);
                } else {
                    $collection->addAttributeToFilter($attribute, $value);
                }
            }

            return $collection->getSize();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get revenue data for multiple products
     *
     * @param array $productIds
     * @return array
     */
    public function getRevenueDataForProducts(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        try {
            $connection = $this->revenueRankingResource->getConnection();
            $select = $connection->select()
                ->from($this->revenueRankingResource->getMainTable(), ['product_id', 'revenue', 'adjusted_revenue', 'ranking'])
                ->where('product_id IN (?)', $productIds);

            $results = $connection->fetchAll($select);
            
            // Convert to associative array with product_id as key
            $revenueData = [];
            foreach ($results as $row) {
                $revenueData[$row['product_id']] = $row;
            }

            return $revenueData;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get products with revenue data in current context
     *
     * @param int $limit
     * @return array
     */
    public function getProductsWithRevenueData($limit = 20)
    {
        try {
            // Get current product collection
            $layer = $this->layerResolver->get();
            if ($layer) {
                $collection = $layer->getProductCollection();
            } else {
                $collection = $this->productCollectionFactory->create();
                $collection->addAttributeToSelect('*');
                $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                $collection->addAttributeToFilter('visibility', ['in' => $this->visibility->getVisibleInSiteIds()]);
            }

            $collection->setPageSize($limit);
            $productIds = $collection->getAllIds();

            // Get revenue data for these products
            $revenueData = $this->getRevenueDataForProducts($productIds);

            // Combine product data with revenue data
            $productsWithRevenue = [];
            foreach ($collection as $product) {
                $productId = $product->getId();
                $productsWithRevenue[] = [
                    'product' => $product,
                    'revenue_data' => $revenueData[$productId] ?? null,
                    'has_revenue_data' => isset($revenueData[$productId])
                ];
            }

            return $productsWithRevenue;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get revenue ranking for current context
     *
     * @return array
     */
    public function getRevenueRanking()
    {
        try {
            $connection = $this->revenueRankingResource->getConnection();
            $select = $connection->select()
                ->from($this->revenueRankingResource->getMainTable(), ['product_id', 'revenue', 'adjusted_revenue', 'ranking'])
                ->order('adjusted_revenue DESC')
                ->limit(50);

            return $connection->fetchAll($select);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if current page has layered navigation filters
     *
     * @return bool
     */
    public function hasActiveFilters()
    {
        $params = $this->request->getParams();
        
        // Remove common non-filter parameters
        $filterParams = array_diff_key($params, array_flip([
            'id', 'p', 'product_list_dir', 'product_list_order', 'product_list_limit', 'product_list_mode'
        ]));

        return !empty($filterParams);
    }

    /**
     * Get active filter parameters
     *
     * @return array
     */
    public function getActiveFilters()
    {
        $params = $this->request->getParams();
        
        // Remove common non-filter parameters
        $filterParams = array_diff_key($params, array_flip([
            'id', 'p', 'product_list_dir', 'product_list_order', 'product_list_limit', 'product_list_mode'
        ]));

        return $filterParams;
    }

    /**
     * Get revenue data summary for current context
     *
     * @return array
     */
    public function getRevenueSummary()
    {
        try {
            $productsWithRevenue = $this->getProductsWithRevenueData(100);
            $totalProducts = count($productsWithRevenue);
            $productsWithData = array_filter($productsWithRevenue, function ($item) {
                return $item['has_revenue_data'];
            });

            $totalRevenue = 0;
            $totalAdjustedRevenue = 0;
            $revenueCount = 0;

            foreach ($productsWithData as $item) {
                $revenueData = $item['revenue_data'];
                $totalRevenue += (float) $revenueData['revenue'];
                $totalAdjustedRevenue += (float) $revenueData['adjusted_revenue'];
                $revenueCount++;
            }

            return [
                'total_products' => $totalProducts,
                'products_with_revenue_data' => $revenueCount,
                'total_revenue' => $totalRevenue,
                'total_adjusted_revenue' => $totalAdjustedRevenue,
                'average_revenue' => $revenueCount > 0 ? $totalRevenue / $revenueCount : 0,
                'average_adjusted_revenue' => $revenueCount > 0 ? $totalAdjustedRevenue / $revenueCount : 0,
                'revenue_coverage_percentage' => $totalProducts > 0 ? ($revenueCount / $totalProducts) * 100 : 0
            ];
        } catch (\Exception $e) {
            return [
                'total_products' => 0,
                'products_with_revenue_data' => 0,
                'total_revenue' => 0,
                'total_adjusted_revenue' => 0,
                'average_revenue' => 0,
                'average_adjusted_revenue' => 0,
                'revenue_coverage_percentage' => 0
            ];
        }
    }
}
