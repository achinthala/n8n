<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ProductProvider
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Configurable $configurableType
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Configurable $configurableType
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configurableType = $configurableType;
    }

    /**
     * Get configurable products for dropdown
     *
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function getConfigurableProducts(string $searchTerm = '', int $limit = 50): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'sku', 'entity_id']);
        $collection->addAttributeToFilter('type_id', Configurable::TYPE_CODE); // Only configurable products
        $collection->addAttributeToFilter('status', 1); // Enabled products only
        
        if (!empty($searchTerm)) {
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'name', 'like' => '%' . $searchTerm . '%'],
                    ['attribute' => 'sku', 'like' => '%' . $searchTerm . '%']
                ]
            );
        }
        
        $collection->setOrder('name', 'ASC');
        $collection->setPageSize($limit);
        
        $products = [];
        foreach ($collection as $product) {
            $products[] = [
                'value' => $product->getId(),
                'label' => $product->getName() . ' (' . $product->getSku() . ')',
                'sku' => $product->getSku(),
                'name' => $product->getName()
            ];
        }
        
        return $products;
    }

    /**
     * Get child products (SKUs) for a configurable product
     *
     * @param int $configurableProductId
     * @return array
     */
    public function getChildProducts(int $configurableProductId): array
    {
        try {
            $configurableProduct = $this->productRepository->getById($configurableProductId);
            
            if ($configurableProduct->getTypeId() !== Configurable::TYPE_CODE) {
                // This should not happen since we only show configurable products, but handle gracefully
                return [];
            }
            
            // Get child product IDs
            $childProductIds = $this->configurableType->getChildrenIds($configurableProductId);
            $childIds = $childProductIds[0] ?? []; // getChildrenIds returns array of arrays
            
            if (empty($childIds)) {
                return [];
            }
            
            // Create collection of child products with proper attributes
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['sku', 'weight', 'status', 'incstores_pim_variant_display_name', 'incstores_pim_color_display_name']);
            $collection->addFieldToFilter('entity_id', ['in' => $childIds]);
            $collection->addAttributeToFilter('status', 1);
            $collection->setOrder('sku', 'ASC');
            
            $skus = [];
            
            foreach ($collection as $childProduct) {
                $productSku = $childProduct->getSku();
                $variantDisplayName = $childProduct->getData('incstores_pim_variant_display_name');
                $colorDisplayName = $childProduct->getData('incstores_pim_color_display_name');
                
                // Create display name by concatenating variant and color display names
                $displayParts = array_filter([$variantDisplayName, $colorDisplayName]);
                $displayName = !empty($displayParts) ? implode(' - ', $displayParts) : $productSku;
                
                $skus[] = [
                    'value' => $productSku,
                    'label' => $displayName,
                    'sku' => $productSku,
                    'weight' => $this->getProductWeight($childProduct)
                ];
            }
            
            return $skus;
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get product weight (rounded up to nearest integer)
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    private function getProductWeight($product): int
    {
        $weight = $product->getWeight();
        if ($weight === null || $weight === '') {
            return 1; // Default weight
        }
        
        return (int) ceil((float) $weight);
    }

    /**
     * Get product by SKU
     *
     * @param string $sku
     * @return array|null
     */
    public function getProductBySku(string $sku): ?array
    {
        try {
            $product = $this->productRepository->get($sku);
            
            return [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'weight' => $this->getProductWeight($product)
            ];
            
        } catch (\Exception $e) {
            return null;
        }
    }
}