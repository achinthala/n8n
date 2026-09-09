<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Dcw\BestSellingProducts\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class BestSellingProducts extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param CollectionFactory $productCollectionFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        CollectionFactory $productCollectionFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get current product
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get attribute set name for current product
     *
     * @return string
     */
    public function getAttributeSetName()
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return '';
        }

        try {
            $attributeSetId = $product->getAttributeSetId();
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
            return $attributeSet->getAttributeSetName();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get formatted title
     *
     * @return string
     */
    public function getTitle()
    {
        $attributeSetName = $this->getAttributeSetName();
        if ($attributeSetName) {
            return 'Best Selling ' . $attributeSetName;
        }
        return 'Best Selling Products';
    }

    /**
     * Get best selling products with same attribute set
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getItems()
    {
        $items = $this->getData('items');
        if ($items === null) {
            $product = $this->getCurrentProduct();
            if (!$product) {
                $this->setData('items', []);
                return [];
            }

            $attributeSetId = $product->getAttributeSetId();
            $currentProductId = $product->getId();
            $storeId = $this->storeManager->getStore()->getId();

            // Get top-selling products by quantity ordered
            $connection = $this->resourceConnection->getConnection();
            $salesOrderItemTable = $connection->getTableName('sales_order_item');
            $catalogProductEntityTable = $connection->getTableName('catalog_product_entity');
            $salesOrderTable = $connection->getTableName('sales_order');
			
			$eavTable = $connection->getTableName('eav_attribute');

			$visibilityAttributeId = $connection->fetchOne(
				$connection->select()
					->from($eavTable, ['attribute_id'])
					->where('attribute_code = ?', 'visibility')
					->where('entity_type_id = ?', 4) // catalog_product
			);
			$catalogProductEntityIntTable = $connection->getTableName('catalog_product_entity_int');


            // Query to get top-selling products by attribute set
            $select = $connection->select()
				->from(
					['soi' => $salesOrderItemTable],
					[
						'product_id' => 'soi.product_id',
						'qty_ordered' => 'SUM(soi.qty_ordered)'
					]
				)
				->joinInner(
					['so' => $salesOrderTable],
					'soi.order_id = so.entity_id',
					[]
				)
				->joinInner(
					['cpe' => $catalogProductEntityTable],
					'soi.product_id = cpe.entity_id',
					[]
				)
				->joinInner(
					['cpei' => $catalogProductEntityIntTable],
					'cpei.row_id = cpe.row_id
					 AND cpei.attribute_id = ' . (int)$visibilityAttributeId . '
					 AND cpei.store_id = 0',
					[]
				)
				->where('so.status = ?', 'complete')
				->where('cpe.attribute_set_id = ?', $attributeSetId)
				->where('soi.product_id != ?', $currentProductId)
				->where('cpei.value IN (?)', [2, 3, 4]) // Catalog, Search, Catalog & Search
				->group('soi.product_id')
				->order('SUM(soi.qty_ordered) DESC')
				->limit(10);
				

            $topProductIds = $connection->fetchCol($select);

            if (empty($topProductIds)) {
                $this->setData('items', []);
                return [];
            }

            // Load product collection
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*')
                ->addFieldToFilter('entity_id', ['in' => $topProductIds])
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('visibility', ['in' => [
                    Visibility::VISIBILITY_BOTH,
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_IN_SEARCH
                ]])
                ->addAttributeToSelect(['image', 'small_image', 'thumbnail', 'name', 'price', 'special_price'])
                ->setStoreId($storeId)
                ->addStoreFilter($storeId);

            // Maintain order based on sales
            $productsById = [];
            foreach ($collection as $product) {
                $productsById[$product->getId()] = $product;
            }

            $orderedProducts = [];
            foreach ($topProductIds as $productId) {
                if (isset($productsById[$productId])) {
                    $orderedProducts[] = $productsById[$productId];
                }
            }

            $this->setData('items', $orderedProducts);
            return $orderedProducts;
        }
        return $items;
    }

    /**
     * Get item count
     *
     * @return int
     */
    public function getItemCount()
    {
        return count($this->getItems());
    }
}
