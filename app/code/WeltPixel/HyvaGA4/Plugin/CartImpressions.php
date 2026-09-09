<?php

namespace WeltPixel\HyvaGA4\Plugin;

use Hyva\Theme\ViewModel\ProductList;
use Hyva\Theme\ViewModel\Store;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Hyva\Theme\ViewModel\Cart\Items as CartItems;

class CartImpressions
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
     * @param \WeltPixel\GA4\Block\Cart $cartBlock
     * @param $result
     * @return array
     */
    public function afterGetCrosselProductCollection(
        \WeltPixel\GA4\Block\Cart $cartBlock,
        $result
    ) {
        if (is_array($result) && empty($result)) {
            $hyvaCrosssell =  $this->_layout->getBlock('crosssell');
            if (empty($hyvaCrosssell)) {
                return [];
            }

            $productListViewModel = $this->viewModelRegistry->require(ProductList::class);
            $storeViewModel       = $this->viewModelRegistry->require(Store::class);
            $cartItems            = $this->viewModelRegistry->require(CartItems::class);

            $productListViewModel->setPageSize(8);
            $productListViewModel->addFilter('website_id', $storeViewModel->getWebsiteId());
            $productListViewModel->addFilter('visibility', [
                ProductVisibility::VISIBILITY_IN_CATALOG,
                ProductVisibility::VISIBILITY_IN_SEARCH,
                ProductVisibility::VISIBILITY_BOTH,
            ], 'in');

            $result =  $productListViewModel->getCrosssellItems(...$cartItems->getCartItems());
        }

        return $result;
    }

}
