<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 *
 * Example usage of the RevenueRanking custom block
 */

namespace Dcw\RevenueRanking\Example;

use Dcw\RevenueRanking\Block\Product\RevenueRankingBlock;
use Dcw\RevenueRanking\ViewModel\RevenueRankingViewModel;

class UsageExample
{
    /**
     * Example of how to use the RevenueRankingBlock in a custom controller
     */
    public function exampleControllerUsage()
    {
        // This would typically be in a controller action
        $layout = $this->getLayout();
        
        // Create the block
        $revenueBlock = $layout->createBlock(RevenueRankingBlock::class);
        
        // Get various product counts
        $totalProducts = $revenueBlock->getTotalProductCount();
        $filteredProducts = $revenueBlock->getFilteredProductCount();
        
        // Get product count by specific attribute
        $redProducts = $revenueBlock->getProductCountByAttribute('color', 'red');
        
        // Get revenue statistics
        $stats = $revenueBlock->getRevenueStatistics();
        
        // Get top revenue products
        $topProducts = $revenueBlock->getTopRevenueProducts(5);
        
        return [
            'total_products' => $totalProducts,
            'filtered_products' => $filteredProducts,
            'red_products' => $redProducts,
            'stats' => $stats,
            'top_products' => $topProducts
        ];
    }
    
    /**
     * Example of how to use the ViewModel in a template
     */
    public function exampleTemplateUsage()
    {
        // This would be in a .phtml template file
        $templateCode = '
        <?php
        /** @var \Dcw\RevenueRanking\ViewModel\RevenueRankingViewModel $viewModel */
        ?>
        
        <!-- Get revenue summary -->
        <?php $summary = $viewModel->getRevenueSummary(); ?>
        <div class="revenue-summary">
            <h3>Revenue Summary</h3>
            <p>Total Products: <?= $summary[\'total_products\'] ?></p>
            <p>Products with Revenue Data: <?= $summary[\'products_with_revenue_data\'] ?></p>
            <p>Average Revenue: $<?= number_format($summary[\'average_revenue\'], 2) ?></p>
            <p>Revenue Coverage: <?= round($summary[\'revenue_coverage_percentage\'], 1) ?>%</p>
        </div>
        
        <!-- Check for active filters -->
        <?php if ($viewModel->hasActiveFilters()): ?>
            <div class="active-filters">
                <h4>Active Filters:</h4>
                <?php foreach ($viewModel->getActiveFilters() as $filter => $value): ?>
                    <span class="filter-tag"><?= $filter ?>: <?= is_array($value) ? implode(\', \', $value) : $value ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Get products with revenue data -->
        <?php $productsWithRevenue = $viewModel->getProductsWithRevenueData(10); ?>
        <div class="products-with-revenue">
            <h4>Products with Revenue Data (Top 10)</h4>
            <?php foreach ($productsWithRevenue as $item): ?>
                <?php if ($item[\'has_revenue_data\']): ?>
                    <div class="product-item">
                        <span class="product-name"><?= $item[\'product\']->getName() ?></span>
                        <span class="revenue">$<?= number_format($item[\'revenue_data\'][\'adjusted_revenue\'], 2) ?></span>
                        <span class="ranking">#<?= $item[\'revenue_data\'][\'ranking\'] ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        ';
        
        return $templateCode;
    }
    
    /**
     * Example of how to get product counts with layered navigation filters
     */
    public function exampleLayeredNavigationUsage()
    {
        // This would be used when working with Mirasvit layered navigation
        $templateCode = '
        <script type="text/javascript">
        require([\'jquery\'], function($) {
            $(document).ready(function() {
                // Listen for filter changes
                $(document).on(\'contentUpdated\', function() {
                    // Get updated product count
                    var totalProducts = $(\'.revenue-ranking-block .total-products\').text();
                    var filteredProducts = $(\'.revenue-ranking-block .filtered-products\').text();
                    
                    console.log(\'Total Products:\', totalProducts);
                    console.log(\'Filtered Products:\', filteredProducts);
                    
                    // Update any custom elements with the new counts
                    $(\'.custom-product-count\').text(filteredProducts);
                });
                
                // Listen for individual filter item clicks
                $(\'.mst-nav__label-item\').on(\'click\', function() {
                    var filterItem = $(this);
                    var count = filterItem.find(\'.count\').text();
                    var label = filterItem.find(\'.mst-nav__label-item__label\').text();
                    
                    console.log(\'Filter clicked:\', label, \'Count:\', count);
                });
            });
        });
        </script>
        ';
        
        return $templateCode;
    }
    
    /**
     * Example of how to create a custom block that extends RevenueRankingBlock
     */
    public function exampleCustomBlock()
    {
        $customBlockCode = '
        <?php
        namespace Dcw\RevenueRanking\Block\Product;
        
        class CustomRevenueBlock extends RevenueRankingBlock
        {
            /**
             * Get custom revenue metrics
             */
            public function getCustomRevenueMetrics()
            {
                $stats = $this->getRevenueStatistics();
                $summary = $this->getRevenueSummary();
                
                return [
                    \'revenue_per_product\' => $stats[\'total_products\'] > 0 ? $stats[\'avg_revenue\'] : 0,
                    \'top_performer_percentage\' => $this->getTopPerformerPercentage(),
                    \'revenue_growth_trend\' => $this->getRevenueGrowthTrend(),
                    \'market_share\' => $this->getMarketShare()
                ];
            }
            
            /**
             * Calculate top performer percentage
             */
            private function getTopPerformerPercentage()
            {
                $topProducts = $this->getTopRevenueProducts(10);
                $totalProducts = $this->getTotalProductCount();
                
                return $totalProducts > 0 ? (count($topProducts) / $totalProducts) * 100 : 0;
            }
            
            /**
             * Get revenue growth trend (placeholder)
             */
            private function getRevenueGrowthTrend()
            {
                // This would typically query historical data
                return 5.2; // Example growth percentage
            }
            
            /**
             * Calculate market share
             */
            private function getMarketShare()
            {
                $totalRevenue = $this->getRevenueStatistics()[\'avg_revenue\'];
                $marketTotal = 1000000; // Example market total
                
                return $marketTotal > 0 ? ($totalRevenue / $marketTotal) * 100 : 0;
            }
        }
        ';
        
        return $customBlockCode;
    }
}
