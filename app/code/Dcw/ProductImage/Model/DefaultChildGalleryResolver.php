<?php
declare(strict_types=1);

namespace Dcw\ProductImage\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves which child product gallery should render on configurable PDP first paint.
 */
class DefaultChildGalleryResolver
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Configurable $configurableType,
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getDefaultChildId(Product $product): ?int
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return null;
        }

        $parent = $this->loadFullProduct($product);
        $parentId = (int) $parent->getId();

        $spotlightSku = trim((string) $this->request->getParam('option'));
        if ($spotlightSku !== '') {
            try {
                $spotlightChild = $this->productRepository->get($spotlightSku);
                if ($this->isChildOfParent($spotlightChild, $parentId)) {
                    return (int) $spotlightChild->getId();
                }
            } catch (NoSuchEntityException) {
                // Fall through.
            }
        }

        $childIdFromAttributes = $this->resolveChildIdFromParentAttributes($parent);
        if ($childIdFromAttributes !== null) {
            return $childIdFromAttributes;
        }

        return $this->resolveMinPriceChildId($parentId);
    }

    /**
     * Product whose Canto gallery should be rendered on initial PDP load.
     */
    public function getGalleryProduct(Product $product): Product
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return $product;
        }

        $parent = $this->loadFullProduct($product);

        if (!$this->shouldUseChildGallery($parent)) {
            return $parent;
        }

        $childId = $this->getDefaultChildId($parent);

        if ($childId === null) {
            return $parent;
        }

        try {
            return $this->loadFullProduct(
                $this->productRepository->getById($childId)
            );
        } catch (NoSuchEntityException) {
            return $parent;
        }
    }

    private function shouldUseChildGallery(Product $parent): bool
    {
        if ((int) $parent->getData('show_all_color_images') === 1) {
            return true;
        }

        return trim((string) $this->request->getParam('option')) !== '';
    }

    private function loadFullProduct(Product $product): Product
    {
        try {
            return $this->productRepository->getById(
                (int) $product->getId(),
                false,
                (int) $product->getStoreId(),
                true
            );
        } catch (NoSuchEntityException) {
            return $product;
        }
    }

    private function resolveChildIdFromParentAttributes(Product $parent): ?int
    {
        $parentId = (int) $parent->getId();
        $calculatorType = (string) $parent->getData('incstores_pim_calculator_type');

        if ($calculatorType === 'case') {
            $childId = $this->parseChildIdFromAttributeValue(
                $parent->getData('incstores_pim_exact_height_inches'),
                $parentId
            );
            if ($childId !== null) {
                return $childId;
            }
        }

        $childId = $this->parseChildIdFromAttributeValue(
            $parent->getData('incstores_pim_exact_width_inches'),
            $parentId
        );
        if ($childId !== null) {
            return $childId;
        }

        $childId = $this->parseChildIdFromAttributeValue(
            $parent->getData('incstores_pim_exact_length_inches'),
            $parentId
        );
        if ($childId !== null) {
            return $childId;
        }

        if ($calculatorType === 'case') {
            return $this->parseChildIdFromAttributeValue(
                $parent->getData('incstores_pim_coverage'),
                $parentId
            );
        }

        return null;
    }

    private function parseChildIdFromAttributeValue(mixed $value, int $parentId): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parts = explode('_', (string) $value);
        $childId = (int) ($parts[0] ?? 0);

        if ($childId <= 0 || !$this->isChildOfParentId($childId, $parentId)) {
            return null;
        }

        return $childId;
    }

    private function resolveMinPriceChildId(int $parentId): ?int
    {
        $childIds = $this->getChildEntityIds($parentId);
        if ($childIds === []) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();
        $websiteId = (int) $this->storeManager->getStore()->getWebsiteId();

        $select = $connection->select()
            ->from(['cp' => $connection->getTableName('catalog_product_index_price')], ['entity_id'])
            ->where('cp.entity_id IN (?)', $childIds)
            ->where('cp.website_id = ?', $websiteId)
            ->where('cp.customer_group_id = ?', 0)
            ->where('cp.final_price > ?', 0)
            ->order('cp.final_price ASC')
            ->limit(1);

        $childId = $connection->fetchOne($select);

        if (!$childId) {
            return null;
        }

        return (int) $childId;
    }

    private function isChildOfParent(ProductInterface $child, int $parentId): bool
    {
        return $this->isChildOfParentId((int) $child->getId(), $parentId);
    }

    private function isChildOfParentId(int $childId, int $parentId): bool
    {
        return in_array($childId, $this->getChildEntityIds($parentId), true);
    }

    /**
     * @return int[]
     */
    private function getChildEntityIds(int $parentId): array
    {
        $childrenIds = $this->configurableType->getChildrenIds($parentId);
        $childIds = [];

        foreach ($childrenIds as $ids) {
            foreach ($ids as $id) {
                $childIds[] = (int) $id;
            }
        }

        return $childIds;
    }
}
