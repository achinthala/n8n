<?php

declare(strict_types=1);

namespace Dcw\ConfigurableProduct\Plugin\Magento\ConfigurableProduct\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData as MagentoConfigurableAttributeData;

class ConfigurableAttributeData
{
    private const ATTRIBUTES = 'attributes';
    private const OPTIONS = 'options';
    private const PRICE = 'price';
    private const PRODUCTS = 'products';
    private const ATTRIBUTE_CODE = 'incstores_pim_color_axis';
    private const CODE = 'code';
    private const SORT_FALLBACK = 99999.99;

    public function afterGetAttributesData(
        MagentoConfigurableAttributeData $subject,
        array $result,
        ProductInterface $product
    ): array {
        $productPrices = $this->getProductPrices($product);

        foreach ($result[self::ATTRIBUTES] as &$attribute) {
            if ($attribute[self::CODE] === self::ATTRIBUTE_CODE) {
                $attributeOptions = array_map(
                    fn ($option) => $this->getLowestAttributeOptionPrice($option, $productPrices),
                    $attribute[self::OPTIONS]
                );

                asort($attributeOptions);

                foreach ($attributeOptions as $index => $price) {
                    $attributeOptions[$index] = $attribute[self::OPTIONS][$index];
                }

                $attribute[self::OPTIONS] = array_values($attributeOptions);
            }
        }

        return $result;
    }

    private function getProductPrices(ProductInterface $product): array
    {
        $usedProducts = $product->getTypeInstance()?->getUsedProducts($product);

        $productPrice = [];


        foreach ($usedProducts as $usedProduct) {
            $usedProductId = (int)$usedProduct->getId();

            $productPrice[$usedProductId] = (float)$usedProduct->getFinalPrice();

            $tierPrice = (float)$usedProduct->getTierPrice(1);

            if ($tierPrice > 0 && $tierPrice < $productPrice[$usedProductId]) {
                $productPrice[$usedProductId] = $tierPrice;
            }
        }

        return $productPrice;
    }

    private function getLowestAttributeOptionPrice(array $option, array $productPrices): float
    {
        if (empty($option[self::PRODUCTS])) {
            return self::SORT_FALLBACK;
        }

        $lowestPrice = self::SORT_FALLBACK;

        foreach ($option[self::PRODUCTS] as $productId) {
            $price = $productPrices[(int)$productId] ?? self::SORT_FALLBACK;

            if ($price < $lowestPrice) {
                $lowestPrice = $price;
            }
        }

        return $lowestPrice;
    }
}
