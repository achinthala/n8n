<?php

namespace WeltPixel\GA4\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurableProducts  extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface */
    protected $viewItemBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface $viewItemBuilder
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface $viewItemBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->viewItemBuilder = $viewItemBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Catalog\Model\Product $childProduct
     * @param boolean $isVariantEnabled
     * @param array $configurableOptions
     * @return array|boolean
     */
    public function getViewItemEventDataForSimpleProduct($childProduct, $isVariantEnabled, $configurableOptions)
    {
        $variant = '';
        if ($isVariantEnabled) {
            $variant = $this->getVariantForSimpleProduct($childProduct, $configurableOptions);
        }
        $viewItemEvent = $this->viewItemBuilder->getViewItemEvent($childProduct->getId(), $variant);
        $viewItemEventData = $viewItemEvent->getParams();

        if ($viewItemEventData && isset($viewItemEventData['events'])) {
            $ecommerceData = $viewItemEventData['events'][0]['params'];
            unset($ecommerceData['page_location']);

            $result = [
                'ecommerce' => $ecommerceData,
                'event' => 'view_item'
            ];

            return $result;
        }

        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Product $childProduct
     * @param array $configurableOptions
     * @return string
     */
    public function getVariantForSimpleProduct($childProduct, $configurableOptions)
    {
        $variant = [];
        foreach ($configurableOptions as $productAttributeOptions) {
            foreach ($productAttributeOptions as $attributeOption) {
                if ($attributeOption['sku'] == $childProduct->getSku()) {
                    $variant[] = $attributeOption['super_attribute_label'] . ": " . $attributeOption['option_title'];
                }
            }
        }

        if ($variant) {
            return implode(' | ', $variant);
        }

        return '';
    }

    /**
     * @param integer $productId
     * @return false|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductById($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Exception $ex) {
            return false;
        }

        return $product;
    }
}
