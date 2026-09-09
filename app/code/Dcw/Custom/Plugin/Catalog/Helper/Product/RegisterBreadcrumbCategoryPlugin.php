<?php
declare(strict_types=1);

namespace Dcw\Custom\Plugin\Catalog\Helper\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

/**
 * When PDP loads without request category and session has no usable last-visited category,
 * core leaves registry(current_category) empty so breadcrumbs show only Home → product.
 * Register the deepest active assigned category so breadcrumb path matches catalog structure
 * without adding ?category= to product URLs.
 */
class RegisterBreadcrumbCategoryPlugin
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Registry $registry,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param bool|Product $result
     * @return bool|Product
     */
    public function afterInitProduct(
        CatalogProductHelper $subject,
        $result,
        $productId,
        $controller,
        $params = null
    ) {
        if ($result === false || !$result instanceof ProductInterface) {
            return $result;
        }

        if ($this->request->getFullActionName() !== 'catalog_product_view') {
            return $result;
        }

        if ($this->registry->registry('current_category')) {
            return $result;
        }

        $product = $result;
        if (!$product instanceof Product) {
            return $result;
        }

        $categoryId = $this->resolveDeepestCategoryId($product);
        if ($categoryId === null) {
            return $result;
        }

        if (!$product->canBeShowInCategory($categoryId)) {
            return $result;
        }

        try {
            $category = $this->categoryRepository->get(
                $categoryId,
                $this->storeManager->getStore()->getId()
            );
        } catch (NoSuchEntityException) {
            return $result;
        }

        $product->setCategory($category);
        $this->registry->register('current_category', $category);

        return $result;
    }

    private function resolveDeepestCategoryId(Product $product): ?int
    {
        $ids = $product->getCategoryIds();
        if (!$ids) {
            return null;
        }

        $storeRootId = (int) $this->storeManager->getStore()->getRootCategoryId();
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['entity_id', 'level', 'position'])
            ->addAttributeToFilter('entity_id', ['in' => $ids])
            ->addAttributeToFilter('entity_id', ['neq' => 1])
            ->addAttributeToFilter('entity_id', ['neq' => $storeRootId])
            ->addIsActiveFilter()
            ->setStore($this->storeManager->getStore());

        $collection->setOrder('level', 'DESC');
        $collection->setOrder('position', 'DESC');

        $category = $collection->getFirstItem();
        $id = (int) $category->getId();

        return $id > 0 ? $id : null;
    }
}
