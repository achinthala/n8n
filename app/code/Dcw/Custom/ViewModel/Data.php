<?php
declare(strict_types=1);

namespace Dcw\Custom\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Dcw\Custom\Block\Custom as CustomBlock;
use Dcw\FlooringCalculation\Block\Index as FlooringCalculationBlock;
use Dcw\BrandLogo\Block\Index as BrandLogoBlock;
use Dcw\SampleProduct\Block\SampleData as SampleProductBlock;
use Dcw\ProductImage\Helper\Data as ProductImageHelper;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Dcw\AllCategories\Block\CategoryList;
use Magento\Catalog\Block\Product\AbstractProduct;
use Dcw\CompareRestriction\Block\Index as CompareRestrictionBlock;
use Dcw\ShopByCategory\Block\MenuConfigvalues;
use Dcw\Notifications\Block\Notification as NotificationsBlock;

class Data implements ArgumentInterface
{
    public function __construct(
        private readonly CustomBlock $customBlock,
        private readonly FlooringCalculationBlock $flooringCalculationBlock,
        private readonly BrandLogoBlock $brandLogoBlock,
        private readonly SampleProductBlock $sampleProductBlock,
        private readonly ProductImageHelper $productImageHelper,
        private readonly CatalogImageHelper $catalogImageHelper,
        private readonly CategoryList $categoryList,
        private readonly AbstractProduct $abstractProduct,
        private readonly CompareRestrictionBlock $compareRestrictionBlock,
        private readonly MenuConfigvalues $menuConfigvalues,
        private readonly NotificationsBlock $notificationsBlock
    ) {
    }

    public function displayBestSellerArray()
    {
        $displayBestSellerArray = [];
        $currentCategory = $this->customBlock->getCurrentCategory();

        if ($currentCategory) {
            $displayBestSellerArray = $this->flooringCalculationBlock->getBestSellerItems($currentCategory);
        }

        return $displayBestSellerArray;
    }

    public function getCantoUrlAndSwatchValue(int $productId)
    {
        $getCantoUrlAndSwatchValue = [];

        $loadProduct = $this->customBlock->getLoadProduct($productId);

        if ($loadProduct) {
            $cantoImage = $this->productImageHelper->getMainImageUrl($loadProduct);
            $mainImageData = $this->catalogImageHelper->getDefaultPlaceholderUrl('small_image');

            if ($cantoImage != "") {
                $imagesDimensionMedium = $this->productImageHelper->getThumbnailImageDimension();
                $mainImageData = $cantoImage.'/-B'.$imagesDimensionMedium;
            }

            $getCantoUrlAndSwatchValue['cantourl'] = $mainImageData;

            $getCantoUrlAndSwatchValue['swatchimageval'] = false;
            $optionIdvalue = $loadProduct->getQuickShip();

            if ($optionIdvalue != "") {
                $swatchimageval = $this->categoryList->getSwatchImage($optionIdvalue);
                $getCantoUrlAndSwatchValue['swatchimageval'] = $swatchimageval;
            }

            //sample product skus & color count
            $samplesku = $configColorArray = [];

            if ($loadProduct->getSampleProductSku()) {
                $samplesku[] = $loadProduct->getSampleProductSku();
            }

            if ($loadProduct->getTypeId()=='configurable') {
                $_children = $loadProduct->getTypeInstance()->getUsedProducts($loadProduct);

                foreach ($_children as $child) {
                    if ($child->getSampleProductSku()) {
                        $samplesku[] = $child->getSampleProductSku(); //sample sku
                        $configColorArray[] = $child->getIncstoresPimColorAxis(); //color count
                    }
                }
            }

            $getCantoUrlAndSwatchValue['samplesku'] = $samplesku;
            $getCantoUrlAndSwatchValue['colorcount'] = $configColorArray;
        }

        return $getCantoUrlAndSwatchValue;
    }

    public function getBrandLogo(int $productId): string
    {
        return $this->brandLogoBlock->getBrandLogo($productId);
    }

    public function getSampleBlock($productId)
    {
        return $this->sampleProductBlock->getsampleproductsku($productId);
    }

    public function getLoadProduct(int $productId)
    {
        return $this->customBlock->getLoadProduct($productId);
    }

    /**
     * Home, PLP, CLP (category/CMS), and PDP — second-image hover (desktop) / rotate (mobile).
     */
    private const SECOND_IMAGE_PAGE_ACTIONS = [
        'cms_index_index',
        'catalog_category_view',
        'catalog_product_view',
        'cms_page_view',
    ];

    public function isSecondImagePageContext(): bool
    {
        return in_array(
            $this->getRequest()->getFullActionName(),
            self::SECOND_IMAGE_PAGE_ACTIONS,
            true
        );
    }

    public function getListHoverImageUrl(int $productId): ?string
    {
        $product = $this->getLoadProduct($productId);

        if (!$product) {
            return null;
        }

        return $this->productImageHelper->getListHoverImageUrl($product);
    }

    public function getCategoryListImageUrl(string $imageUrl): string
    {
        return $this->productImageHelper->applyCategoryListImageDimension($imageUrl);
    }

    public function getCategoryListImageDimension(): string
    {
        return $this->productImageHelper->getCategoryListImageDimension();
    }

    public function getAttributeById(int $attributeId): AttributeInterface
    {
        return $this->customBlock->getAttributeById($attributeId);
    }

    public function getSwatchProducts(ProductInterface $product): string
    {
        return $this->customBlock->getSwatchProducts($product);
    }

    public function getCurrentCurrencySymbol()
    {
        return $this->customBlock->getCurrentCurrencySymbol();
    }

    public function getConfigurableAttributeOptions($product)
    {
        return $this->customBlock->getConfigurableAttributeOptions($product);
    }

    public function getConfigurableJsonConfig($product): string
    {
        return $this->customBlock->getConfigurableJsonConfig($product);
    }

    public function getChildSaleableMap($product, array $productAttributeOptions): array
    {
        return $this->customBlock->getChildSaleableMap($product, $productAttributeOptions);
    }

    public function getRequest()
    {
        return $this->customBlock->getRequest();
    }

    public function getAttributeByCode(string $attributeCode): AttributeInterface
    {
        return $this->customBlock->getAttributeByCode($attributeCode);
    }

    public function getSwatchImage($optionIdvalue)
    {
        return $this->categoryList->getSwatchImage($optionIdvalue);
    }

    public function getAllCategoryId()
    {
        return $this->customBlock->getAllCategoryId();
    }

    public function getLoadProductBySku($sku)
    {
        return $this->customBlock->getLoadProductBySku($sku);
    }

    public function getParentId($productId)
    {
        return $this->customBlock->getParentId($productId);
    }

    public function abstractProductBlock()
    {
        return $this->abstractProduct;
    }

    public function getCoreSession()
    {
        return $this->customBlock->getCoreSession();
    }

    public function getCurrentUrl(): string
    {
        return $this->customBlock->getCurrentUrl();
    }

    public function getRedirectUrl(): string
    {
        return $this->customBlock->getRedirectUrl();
    }

    public function getCategoryById($catId)
    {
        return $this->customBlock->getCategoryById($catId);
    }

    public function getCurrentCategory()
    {
        return $this->customBlock->getCurrentCategory();
    }

    public function getCurrentProduct()
    {
        return $this->customBlock->getCurrentProduct();
    }

    public function compareRestrictionBlock()
    {
        return $this->compareRestrictionBlock;
    }

    public function getCustomerSession()
    {
        return $this->customBlock->getCustomerSession();
    }

    public function menuConfigvalues()
    {
        return $this->menuConfigvalues;
    }

    public function getNotificationsBlock()
    {
        return $this->notificationsBlock;
    }

    public function getChildLength(ProductInterface $product): string
    {
        return $this->customBlock->getChildLength($product);
    }

    public function getConfigValue(string $path)
    {
        return $this->customBlock->getConfigValue($path);
    }
}
