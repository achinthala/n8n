<?php

namespace Dcw\RevenueRanking\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\App\RequestInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends AbstractHelper
{
    protected $layerResolver;
    
    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        LayerResolver $layerResolver,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->layerResolver = $layerResolver;
        $this->request = $request;
    }

    /**
     * Get active filters count
     *
     * @return int
     */
    public function getActiveFilterCount()
    {
        $layer = $this->layerResolver->get();
        $filters = $layer->getState()->getFilters(); // same as $block->getActiveFilters()
        return count($filters);
    }

    /**
     * Get active filters (with labels and values)
     *
     * @return \Magento\Catalog\Model\Layer\Filter\Item[]
     */
    public function getActiveFilters()
    {
        $layer = $this->layerResolver->get();
        return $layer->getState()->getFilters();
    }
    
    /**
     * Check if product_list_order is revenue_ranking
     *
     * @return bool
     */
    public function isRevenueRanking()
    {
        $paramValue = $this->request->getParam('product_list_order');
        return $paramValue === 'revenue_ranking';
    }
}
