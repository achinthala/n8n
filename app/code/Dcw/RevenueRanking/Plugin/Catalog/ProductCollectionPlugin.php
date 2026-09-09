<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Plugin\Catalog;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\RequestInterface;

class ProductCollectionPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;
    
    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }
    
    /**
     * Handle revenue ranking sort
     */
    public function beforeLoad(Collection $subject)
    {
        // Only handle revenue ranking sort if requested
        if ($this->request && $this->request->getParam('product_list_order') === 'revenue_ranking') {
            $this->handleRevenueRankingSort($subject);
        }
        
        // Always add revenue ranking data for Buy % calculation
        $this->addRevenueRankingData($subject);
    }
    
    /**
     * Handle revenue ranking sort using custom table
     */
    private function handleRevenueRankingSort(Collection $collection)
    {
        try {
            // Get the direction
            $direction = ($this->request->getParam('product_list_dir') === 'asc') ? 'ASC' : 'DESC';
            
            // Join with our custom revenue table
            $collection->getSelect()->joinLeft(
                ['revenue' => $collection->getTable('custom_product_revenue_ranking')],
                'e.entity_id = revenue.product_id',
                ['adjusted_revenue' => 'COALESCE(revenue.adjusted_revenue, 0)']
            );
            
            // Order by adjusted revenue
            $collection->getSelect()->order('adjusted_revenue ' . $direction);
            
            // Add secondary sort by product ID for consistency
            $collection->getSelect()->order('e.entity_id ASC');
            
        } catch (\Exception $e) {
            // If anything fails, just continue without breaking
        }
    }
    
    /**
     * Add revenue ranking data to collection for Buy % calculation
     */
    private function addRevenueRankingData(Collection $collection)
    {
        try {
            // Join with our custom revenue table to get revenue data
            $collection->getSelect()->joinLeft(
                ['revenue' => $collection->getTable('custom_product_revenue_ranking')],
                'e.entity_id = revenue.product_id',
                ['revenue_ranking' => 'COALESCE(revenue.adjusted_revenue, 0)']
            );
        } catch (\Exception $e) {
            // If anything fails, just continue without breaking
        }
    }
}
