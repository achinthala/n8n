<?php
namespace WeltPixel\GA4\Block;

/**
 * Class \WeltPixel\GA4\Block\Category
 */
class Category extends \WeltPixel\GA4\Block\Core
{

    protected $_productCollection = [];

    /**
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection|null
     */
    public function getProductCollection()
    {
        if (!empty($this->_productCollection)) {
            return $this->_productCollection;
        }

        $currentCategory = $this->getCurrentCategory();
        $displayMode = $currentCategory->getData('weltpixel_sc_layout') ?? '';
        if ($currentCategory &&
            ( $currentCategory->getData('display_mode') == \Magento\Catalog\Model\Category::DM_PAGE
                || $displayMode == 'subcategories_images')
        ) {
            return [];
        }

        /** @var \Magento\Catalog\Block\Product\ListProduct $categoryProductListBlock */
        $categoryProductListBlock = $this->_layout->getBlock('category.products.list');

        if (empty($categoryProductListBlock)) {
            return [];
        }

        if ($this->helper->isSmileElasticSuiteEnabled() || $this->helper->isLoadListingBlockEnabled()) {
            $categoryProductListBlock->toHtml();
        }
        // Fetch the current collection from the block and set pagination
        $collection = clone $categoryProductListBlock->getLoadedProductCollection();
        $collection->setCurPage($this->getCurrentPage())->setPageSize($this->getLimit());

        $blockName = $categoryProductListBlock->getToolbarBlockName();
        $toolbarLayout = false;


        if ($blockName) {
            $toolbarLayout = $this->_layout->getBlock($blockName);
        }

        if ($toolbarLayout) {
            // use sortable parameters
            $orders = $categoryProductListBlock->getAvailableOrders();
            if ($orders) {
                $toolbarLayout->setAvailableOrders($orders);
            }
            $sort = $categoryProductListBlock->getSortBy();
            if ($sort) {
                $toolbarLayout->setDefaultOrder($sort);
            }
            $dir = $categoryProductListBlock->getDefaultDirection();
            if ($dir) {
                $toolbarLayout->setDefaultDirection($dir);
            }
            $modes = $categoryProductListBlock->getModes();
            if ($modes) {
                $toolbarLayout->setModes($modes);
            }
            $toolbarLayout->setCollection($collection);
        }


        $this->_productCollection = $collection;
        return $collection;
    }

    /**
     * @return int
     */
    protected function getLimit()
    {
        /** @var \Magento\Catalog\Block\Product\ProductList\Toolbar $productListBlockToolbar */
        $productListBlockToolbar = $this->_layout->getBlock('product_list_toolbar');
        if (empty($productListBlockToolbar)) {
            return 9;
        }

        return (int) $productListBlockToolbar->getLimit();
    }

    /**
     * @return int
     */
    protected function getCurrentPage()
    {
        $page = (int) $this->_request->getParam('p');
        if (!$page) {
            return 1;
        }

        return $page;
    }

}
