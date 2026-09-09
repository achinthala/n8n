<?php

namespace WeltPixel\HyvaGA4\Plugin;

use Hyva\Theme\ViewModel\ProductList;
use Hyva\Theme\ViewModel\Store;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;

class ProductImpressions
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * @var \Hyva\Theme\Model\ViewModelRegistry
     */
    protected $viewModelRegistry;

    /**
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Hyva\Theme\Model\ViewModelRegistry $viewModelRegistry
     */
    public function __construct(
        \WeltPixel\GA4\Helper\Data $helper,
        \Magento\Framework\View\LayoutInterface $layout,
        \Hyva\Theme\Model\ViewModelRegistry $viewModelRegistry
    ) {
        $this->helper = $helper;
        $this->_layout = $layout;
        $this->viewModelRegistry = $viewModelRegistry;
    }

    /**
     * @param \WeltPixel\GA4\Block\Product $productBlock
     * @param $result
     * @return array
     */
    public function afterGetRelatedProductCollection(
        \WeltPixel\GA4\Block\Product $productBlock,
        $result
    ) {
        if (is_array($result) && empty($result)) {
            $hyvaRelated =  $this->_layout->getBlock('related');
            if (empty($hyvaRelated)) {
                return [];
            }

            $productListViewModel = $this->viewModelRegistry->require(ProductList::class);
            $storeViewModel       = $this->viewModelRegistry->require(Store::class);

            $productListViewModel->setPageSize(8);
            $productListViewModel->addFilter('website_id', $storeViewModel->getWebsiteId());
            $productListViewModel->addFilter('visibility', [
                ProductVisibility::VISIBILITY_IN_CATALOG,
                ProductVisibility::VISIBILITY_IN_SEARCH,
                ProductVisibility::VISIBILITY_BOTH,
            ], 'in');

            $result = $productListViewModel->getLinkedItems('related', $hyvaRelated->getProduct());

        }

        return $result;
    }

    /**
     * @param \WeltPixel\GA4\Block\Product $productBlock
     * @param $result
     * @return array|\Magento\Catalog\Api\Data\ProductInterface[]|mixed
     */
    public function afterGetUpsellProductCollection(
        \WeltPixel\GA4\Block\Product $productBlock,
        $result
    ) {
        if (is_array($result) && empty($result)) {
            $hyvaUpsell =  $this->_layout->getBlock('upsell');
            if (empty($hyvaUpsell)) {
                return [];
            }

            $productListViewModel = $this->viewModelRegistry->require(ProductList::class);
            $storeViewModel       = $this->viewModelRegistry->require(Store::class);

            $productListViewModel->setPageSize(8);
            $productListViewModel->addFilter('website_id', $storeViewModel->getWebsiteId());
            $productListViewModel->addFilter('visibility', [
                ProductVisibility::VISIBILITY_IN_CATALOG,
                ProductVisibility::VISIBILITY_IN_SEARCH,
                ProductVisibility::VISIBILITY_BOTH,
            ], 'in');

            $result = $productListViewModel->getLinkedItems('upsell', $hyvaUpsell->getProduct());

        }

        return $result;
    }
}
