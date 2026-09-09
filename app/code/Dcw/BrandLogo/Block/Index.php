<?php

declare(strict_types=1);

namespace Dcw\BrandLogo\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 *  BrandLogo Block
 */
class Index extends Template
{
    private array $brandLogo = []; // Memoization

    public function __construct(
        Context $context,
        private readonly CollectionFactory $categoryCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getBrandLogo(int $productId): string
    {
        if (isset($this->brandLogo[$productId])) {
            return $this->brandLogo[$productId];
        }

        $product = $this->getProductById($productId);

        $brandLogoImgHtml = '';

        if ($product) {
            $categoryIds = $product->getCategoryIds();

            if (!empty($categoryIds)) {
                $baseUrl = $this->storeManager->getStore()->getBaseUrl();

                // Select all attributes or specify required ones
                $categoryCollection = $this->categoryCollectionFactory->create();

                $categoryCollection->addAttributeToSelect(['entity_id', 'name', 'is_brand', 'cat_brand_image'])
                    ->addAttributeToFilter('entity_id', ['in' => $categoryIds]);

                foreach ($categoryCollection as $category) {
                    if ((bool)$category->getIsBrand() && $category->getCatBrandImage()) {
                        $brandLogo = str_replace(
                            '//media',
                            '/media',
                            $baseUrl . $category->getCatBrandImage()
                        );

                        $brandLogoImgHtml = sprintf(
                            '<img src="%s" width="120" height="45" alt="%s" title="%s"/><span class="brand-title">%s</span>',
                            $brandLogo,
                            $category->getName(),
                            $category->getName(),
                            $category->getName()
                        );
                        break;
                    }
                }

                $this->brandLogo[$productId] = $brandLogoImgHtml;
            }
        }

        return $brandLogoImgHtml;
    }

    /**
     * @param int $productId
     * @return ProductInterface|Product
     */
    public function getProductById($productId)
    {
        try {
            return $this->productRepository->getById((int)$productId);
        } catch (NoSuchEntityException $exception) {
            return false;
        }
    }
}
