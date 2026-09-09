<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Model;

use Magento\Framework\Model\AbstractModel;

class RevenueRanking extends AbstractModel
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init(\Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking::class);
    }

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku)
    {
        return $this->setData('sku', $sku);
    }

    /**
     * Get Adjusted Revenue
     *
     * @return float
     */
    public function getAdjustedRevenue()
    {
        return $this->getData('adjusted_revenue');
    }

    /**
     * Set Adjusted Revenue
     *
     * @param float $adjustedRevenue
     * @return $this
     */
    public function setAdjustedRevenue($adjustedRevenue)
    {
        return $this->setData('adjusted_revenue', $adjustedRevenue);
    }
}
