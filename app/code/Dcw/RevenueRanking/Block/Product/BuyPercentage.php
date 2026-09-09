<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Block\Product;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Block class for calculating Buy Percentage based on product revenue.
 */
class BuyPercentage extends Template
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Product|null
     */
    private $currentProduct;

    /**
     * @var int|null
     */
    private $totalProductsInResults;

    /**
     * BuyPercentage constructor.
     *
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Set current product
     *
     * @param Product $product
     * @return $this
     */
    public function setCurrentProduct(Product $product)
    {
        $this->currentProduct = $product;
        return $this;
    }

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getCurrentProduct()
    {
        return $this->currentProduct;
    }

    /**
     * Set total products in search results
     *
     * @param int $totalProducts
     * @return $this
     */
    public function setTotalProductsInResults($totalProducts)
    {
        $this->totalProductsInResults = $totalProducts;
        return $this;
    }

    /**
     * Get total products in search results
     *
     * @return int
     */
    public function getTotalProductsInResults()
    {
        if ($this->totalProductsInResults === null) {
            $this->totalProductsInResults = $this->calculateTotalProductsInResults();
        }
        return $this->totalProductsInResults;
    }

    /**
     * Calculate total products in current search results
     *
     * @return int
     */
    private function calculateTotalProductsInResults()
    {
        try {
            // Get the current collection from the layout
            $collection = $this->getLayout()->getBlock('category.products.list');
            if ($collection && method_exists($collection, 'getCollection')) {
                return $collection->getCollection()->getSize();
            }

            // Fallback: Get collection from registry or create new one
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter(
                'status',
                \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
            );
            $collection->addAttributeToFilter(
                'visibility',
                ['in' => \Magento\Catalog\Model\Product\Visibility::getVisibleInSiteIds()]
            );

            return $collection->getSize();
        } catch (\Exception $e) {
            return 1; // Fallback to prevent division by zero
        }
    }

    /**
     * Get product revenue
     *
     * @param Product|null $product
     * @return float
     */
    public function getProductRevenue(Product $product = null)
    {
        if ($product === null) {
            $product = $this->getCurrentProduct();
        }

        if (!$product) {
            return 0;
        }

        // Try to get revenue from revenue_ranking attribute
        $revenue = $product->getData('revenue_ranking');
        if ($revenue && is_numeric($revenue)) {
            return (float) $revenue;
        }

        // Fallback: Get from custom table
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking::class);
            $revenueData = $resource->getByProductId($product->getId());

            if ($revenueData && isset($revenueData['adjusted_revenue'])) {
                return (float) $revenueData['adjusted_revenue'];
            }
        } catch (\Exception $e) {
            return 0; // If anything fails, return 0
        }

        return 0;
    }

    /**
     * Calculate Buy Percentage
     *
     * @param Product|null $product
     * @return float
     */
    public function getBuyPercentage(Product $product = null)
    {
        $productRevenue = $this->getProductRevenue($product);
        $totalProducts = $this->getTotalProductsInResults();

        if ($totalProducts <= 0) {
            return 0;
        }

        // Buy % = (Product Revenue / Total Products in Results) × 100
        $percentage = ($productRevenue / $totalProducts) * 100;

        return round($percentage, 1);
    }

    /**
     * Get formatted Buy Percentage
     *
     * @param Product|null $product
     * @return string
     */
    public function getFormattedBuyPercentage(Product $product = null)
    {
        $percentage = $this->getBuyPercentage($product);
        return $percentage . '% Buy This';
    }

    /**
     * Check if product has revenue data
     *
     * @param Product|null $product
     * @return bool
     */
    public function hasRevenueData(Product $product = null)
    {
        return $this->getProductRevenue($product) > 0;
    }
}
