<?php
declare(strict_types=1);

namespace Dcw\Custom\Block;

use Amasty\Extrafee\Api\FeesInformationManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Cart;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Custom extends Template
{
    private array $childLengthCache = []; // Memoization
    private array $swatchProducts = []; // Memoization
    private array $childSaleableMapCache = []; // Memoization

    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductFactory $productFactory,
        private readonly UrlInterface $urlInterface,
        private readonly RedirectInterface $redirect,
        private readonly SessionManagerInterface $coreSession,
        private readonly Http $request,
        private readonly Configurable $configurable,
        private readonly Session $customerSession,
        private readonly Config $eavConfig,
        private readonly Cart $cart,
        private readonly OrderItemRepositoryInterface $orderItemRepository,
        private readonly FeesInformationManagementInterface $feesInformationManagement,
        private readonly Collection $customerGroup,
        private readonly CompanyManagementInterface $companyManagement,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly Currency $currency,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @deprecated use class Dcw\ShopByCategory\ViewModel\Config method getMenuConfigurationAllCategoryId()
     * @return mixed
     */
    public function getAllCategoryId()
    {
        return $this->scopeConfig->getValue(
            'dcw_menu_configaration/menulinkremovecattab/all_category_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCategoryById($categoryId)
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $exception) {
            $category = null;

            $this->_logger->error($exception->getTraceAsString(), [$categoryId]);
        }

        return $category;
    }

    public function getLoadProduct(int $productId)
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (\Throwable $e) {
            $this->_logger->critical(sprintf(
            'FAILED ProductId=%d | %s | %s',
            $productId,
            get_class($e),
            $e->getMessage()
        ));

            return $this->productFactory->create();
        }
    }

    public function getSwatchProducts(ProductInterface $product): string
    {
        $productId = (int)$product->getId();

        if (isset($this->swatchProducts[$productId])) {
            return $this->swatchProducts[$productId];
        }

        $usedProducts = $product->getTypeInstance()?->getUsedProducts($product) ?? [];

        $result = [];

        foreach ($usedProducts as $usedProduct) {

            $status = $usedProduct->getStatus();
            //if the product is enabled then only store the canto url
            if ($status == ProductStatus::STATUS_ENABLED) {
                $result['swatchUrl'][$usedProduct->getIncstoresPimColorAxis()] = $usedProduct->getIncstoresPimSwatchImageSkuUrl1() . '/-B77' . '/-FWEBP';
                $result['swatchLabelValues'][$usedProduct->getIncstoresPimColorAxis()] = $usedProduct->getIncstoresPimColorDisplayName();
                $result['swatchLabelValues'][$usedProduct->getIncstoresPimVariantAxis()] = $usedProduct->getIncstoresPimVariantDisplayName();
            }
        }

        try {
            $result = $this->serializer->serialize($result);
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getTraceAsString(), [$product->getId()]);

            $result = '{}'; // Empty JSON object
        }

        $this->swatchProducts[$productId] = $result;

        return $result;
    }

    public function getChildLength(ProductInterface $product): string
    {
        $productId = (int)$product->getId();

        if (isset($this->childLengthCache[$productId])) {
            return $this->childLengthCache[$productId];
        }

        $usedProducts = $product->getTypeInstance()?->getUsedProducts($product);

        $result = [];

        foreach ($usedProducts as $usedProduct) {
            $result[$usedProduct->getId()] = $usedProduct->getIncstoresPimExactLengthInches();
        }

        try {
            $result = $this->serializer->serialize($result);
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getTraceAsString(), [$product->getId()]);

            $result = '{}'; // Empty JSON object
        }

        $this->childLengthCache[$productId] = $result;

        return $result;
    }

    public function getLoadProductBySku($sku)
    {
        try {
            return $this->productRepository->get($sku);
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception->getTraceAsString(), [$sku]);

            return $this->productFactory->create();
        }
    }

    public function getCurrentUrl(): string
    {
        return $this->urlInterface->getCurrentUrl();
    }

    public function getRedirectUrl(): string
    {
        return $this->redirect->getRedirectUrl();
    }

    public function getCoreSession()
    {
        return $this->coreSession;
    }

    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function getParentId($childId)
    {
        $product = $this->configurable->getParentIdsByChild($childId);

        return $product[0] ?? null;
    }

    public function getConfigurableAttributeOptions($product)
    {
        return $this->configurable->getConfigurableAttributesAsArray($product);
    }

    /**
     * Get configurable product JSON config for option prices (used in related products).
     *
     * @param Product $product
     * @return string JSON string or empty string if not configurable
     */
    public function getConfigurableJsonConfig(Product $product): string
    {
        if ($product->getTypeId() !== 'configurable') {
            return '';
        }
        try {
            $currentProduct = $this->registry->registry('current_product');
            $this->registry->unregister('current_product');
            $this->registry->register('current_product', $product);
            try {
                $block = $this->getLayout()->createBlock(
                    \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::class
                );
                $block->setData('product', $product);
                $config = $block->getJsonConfig() ?: '';
            } finally {
                $this->registry->unregister('current_product');
                if ($currentProduct) {
                    $this->registry->register('current_product', $currentProduct);
                }
            }
            return $config;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Get saleable map for all configurable children (including out-of-stock).
     * Uses StockRegistry for reliable stock check. Map keys match childCalculatorMap format.
     *
     * @param Product $product
     * @param array $productAttributeOptions
     * @return array<string, bool> mapKey => isSaleable
     */
    public function getChildSaleableMap(Product $product, array $productAttributeOptions): array
    {
        $productId = (int)$product->getId();
        if (isset($this->childSaleableMapCache[$productId])) {
            return $this->childSaleableMapCache[$productId];
        }
        $map = [];
        if ($product->getTypeId() !== 'configurable') {
            $this->childSaleableMapCache[$productId] = $map;
            return $map;
        }
        // Reuse getUsedProducts (cached on $product) instead of N× productRepository->getById.
        $usedProducts = $product->getTypeInstance()?->getUsedProducts($product) ?? [];
        foreach ($usedProducts as $child) {
            $childId = (int)$child->getId();
            if ($child->getStatus() != ProductStatus::STATUS_ENABLED) {
                continue;
            }
            $keyParts = [];
            foreach ($productAttributeOptions as $configAttr) {
                $attrCode = $configAttr['attribute_code'] ?? '';
                $attrId = (int)($configAttr['attribute_id'] ?? $configAttr['id'] ?? 0);
                if ($attrCode && $attrId) {
                    $val = $child->getData($attrCode);
                    if ($val !== null && $val !== '') {
                        $keyParts[] = $attrId . '_' . $val;
                    }
                }
            }
            if (!empty($keyParts)) {
                sort($keyParts);
                $mapKey = implode('|', $keyParts);
                $stockItem = $this->stockRegistry->getStockItem($childId);
                $map[$mapKey] = (bool)$stockItem->getIsInStock();
            }
        }
        $this->childSaleableMapCache[$productId] = $map;
        return $map;
    }

    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    public function getConfigValue(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getAttributeByCode(string $attributeCode): AttributeInterface
    {
        return $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
    }

    public function getCartDetails()
    {
        return $this->cart;
    }

    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    public function getOrderItem($itemId)
    {
        return $this->orderItemRepository->get($itemId);
    }

    public function getAttributeById(int $attributeId): AttributeInterface
    {
        return $this->eavConfig->getAttribute(Product::ENTITY, $attributeId);
    }

    public function getExtraFee($quote)
    {
        return $this->feesInformationManagement->collectQuote($quote);
    }

    public function getCustomerGroups($code, $value)
    {
        return $this->customerGroup->addFieldToFilter($code, $value)?->toOptionArray();
    }

    public function getCompanyByCustomer($id)
    {
        return $this->companyManagement->getByCustomerId($id);
    }

    public function getCompanyData($id)
    {
        return $this->companyRepository->get($id);
    }

    public function getCurrentCurrencySymbol()
    {
        return $this->currency->getCurrencySymbol();
    }
}
