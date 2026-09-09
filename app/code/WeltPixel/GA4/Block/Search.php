<?php
namespace WeltPixel\GA4\Block;

/**
 * Class \WeltPixel\GA4\Block\Search
 */
class Search extends \WeltPixel\GA4\Block\Category
{

    protected $_searchCollection = [];

    /**
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection|null
     */
    public function getProductCollection()
    {
        if (!empty($this->_searchCollection)) {
            return $this->_searchCollection;
        }
        $searchResultListBlock = $this->_layout->getBlock('search_result_list');

        if (empty($searchResultListBlock)) {
            return [];
        }

        if ($this->helper->isSmileElasticSuiteEnabled() || $this->helper->isLoadListingBlockEnabled()) {
            $searchResultListBlock->toHtml();
        }
        $collection = $searchResultListBlock->getLoadedProductCollection();

        $blockName = $searchResultListBlock->getToolbarBlockName();
        $toolbarLayout = false;

        if ($blockName) {
            $toolbarLayout = $this->_layout->getBlock($blockName);
        }

        if ($toolbarLayout) {
            // use sortable parameters
            $orders = $searchResultListBlock->getAvailableOrders();
            if ($orders) {
                $toolbarLayout->setAvailableOrders($orders);
            }
            $sort = $searchResultListBlock->getSortBy();
            if ($sort) {
                $toolbarLayout->setDefaultOrder($sort);
            }
            $dir = $searchResultListBlock->getDefaultDirection();
            if ($dir) {
                $toolbarLayout->setDefaultDirection($dir);
            }
            $modes = $searchResultListBlock->getModes();
            if ($modes) {
                $toolbarLayout->setModes($modes);
            }
            $toolbarLayout->setCollection($collection);
        } else {
            $collection->setCurPage($this->getCurrentPage())->setPageSize($this->getLimit());
        }

        $this->_searchCollection = $collection;
        return $collection;
    }
}
