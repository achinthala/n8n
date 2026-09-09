<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\ViewModel\Admin;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking as RevenueRankingResource;

class RevenueRankingInfo implements ArgumentInterface
{
    /**
     * @var RevenueRankingResource
     */
    private $revenueRankingResource;

    /**
     * @var array
     */
    private $revenueData;

    /**
     * @param RevenueRankingResource $revenueRankingResource
     */
    public function __construct(
        RevenueRankingResource $revenueRankingResource
    ) {
        $this->revenueRankingResource = $revenueRankingResource;
    }

    /**
     * Get revenue data
     *
     * @return array
     */
    public function getRevenueData()
    {
        if ($this->revenueData === null) {
            try {
                $this->revenueData = $this->revenueRankingResource->getTopProductsByRevenue(50);
            } catch (\Exception $e) {
                $this->revenueData = [];
            }
        }
        
        return $this->revenueData;
    }

    /**
     * Get total products with revenue data
     *
     * @return int
     */
    public function getTotalProductsWithRevenue()
    {
        return count($this->getRevenueData());
    }
}
