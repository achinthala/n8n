<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Plugin\Catalog\Block\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar;

class ToolbarPlugin
{
    /**
     * Add revenue ranking sort option
     */
    public function afterGetAvailableOrders(Toolbar $subject, array $result)
    {
        // Add revenue ranking sort option
        $result['revenue_ranking'] = __('Revenue Ranking');
        
        return $result;
    }

    /**
     * Check if revenue ranking sort is current
     */
    public function afterIsOrderCurrent(Toolbar $subject, bool $result, string $order)
    {
        if ($order === 'revenue_ranking') {
            return $subject->getRequest()->getParam('product_list_order') === 'revenue_ranking';
        }
        
        return $result;
    }
}
