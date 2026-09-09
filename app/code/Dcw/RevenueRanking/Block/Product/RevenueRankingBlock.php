<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Block\Product;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Block class for Revenue Ranking functionality.
 *
 * Provides product collection factory access
 * for revenue ranking features in product listings.
 */
class RevenueRankingBlock extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
	
	protected $attributeRepository;
    protected $searchCriteriaBuilder;
	
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
		AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
		$this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $data);
    }
	
	/**
     * Get all product attributes
     *
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     */
    public function getAllProductAttributes()
    {
        // Create search criteria (you can adjust page size if needed)
        $searchCriteria = $this->searchCriteriaBuilder
            ->setPageSize(1000)
            ->create();

        // 'catalog_product' = product entity type
        $result = $this->attributeRepository->getList('catalog_product', $searchCriteria);
		
		$attributes = $this->attributeRepository
                ->getList('catalog_product', $searchCriteria)
                ->getItems();

		$attdata = array();
		foreach ($attributes as $attribute) {
			if (
				$attribute->getIsFilterable() || 
				$attribute->getIsFilterableInSearch()
			) {
				$attdata[] = $attribute->getAttributeCode();
			}
		}

        return $attdata;
    }
}
