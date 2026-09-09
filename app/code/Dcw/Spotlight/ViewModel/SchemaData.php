<?php

declare(strict_types=1);

namespace Dcw\Spotlight\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Psr\Log\LoggerInterface;
use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchData;

class SchemaData implements ArgumentInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var AdvanceSearchData
     */
    private $advanceSearchData;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        AdvanceSearchData $advanceSearchData
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->advanceSearchData = $advanceSearchData;
    }

    public function getSelectedProduct($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);

            $width = $product->getData('incstores_pim_exact_width_inches');
            $length = $product->getData('incstores_pim_exact_length_inches');
            $coverageArea = $product->getIncstoresPimCoverage();
            $minPriceChildProductId = "";

            if (!is_numeric($width) && !is_numeric($length) || !is_numeric($coverageArea)) {
                $minPriceChildProductId = "";
                if (!is_null($width)) {
                    if (strpos($width, '_') !== false) {
                        $configExactWidthArr = explode('_', $width);
                        $width = $configExactWidthArr[1];

                        if ($configExactWidthArr[0] > 0) {
                            $minPriceChildProductId = $configExactWidthArr[0];
                        }
                    }
                }

                if (!is_null($length)) {
                    if (strpos($length, '_') !== false) {
                        $configExactLengthArr = explode('_', $length);
                        $length = $configExactLengthArr[1];

                        if($configExactLengthArr[0] > 0)
                        {
                            $minPriceChildProductId = $configExactLengthArr[0];
                        }
                    }
                }
            }

            if ($minPriceChildProductId) {
                return $minPriceChildProductId;
            }

            return $productId;
        } catch (\Exception $e) {
            $this->logger->error('Error fetching product: ' . $e->getMessage());
        }
    }

    public function getUnitPriceSpecification($productId)
    {
        $product = $this->productRepository->getById($productId);

        return $this->getUnitPriceSpecificationForProduct($product);
    }

    public function getUnitPriceSpecificationForProduct(Product $product): float
    {
        $calculatorType = $product->getAttributeText('incstores_pim_calculator_type');
        $minOrderQty = $product->getData('incstores_pim_min_order_qty') ?? 1;
        $finalPrice = $product->getFinalPrice($minOrderQty);
        $minRollCut = $product->getData('incstores_pim_min_rollcut');

        if ($calculatorType == 'roll') {
            return $finalPrice * $minOrderQty * $minRollCut;
        }

        return $finalPrice * $minOrderQty;
    }

    public function getReferenceQuantityValue($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);

            return $this->getReferenceQuantityValueForProduct($product);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching product: ' . $e->getMessage());
        }
    }

    /**
     * @return float|null
     */
    public function getReferenceQuantityValueForProduct(Product $product)
    {
        try {
            $calculatorType = $product->getAttributeText('incstores_pim_calculator_type');
            $exactLengthRaw = $product->getData('incstores_pim_exact_length_inches');
            $exactWidthRaw = $product->getData('incstores_pim_exact_width_inches');
            $exactLength = $this->resolvePimExactDimensionInches($product, $exactLengthRaw);
            $exactWidth = $this->resolvePimExactDimensionInches($product, $exactWidthRaw);
            $itemPerBox = $product->getData('incstores_pim_items_per_box') ?? 1;
            $minOrderQty = $product->getData('incstores_pim_min_order_qty') ?? 1;
            $minRollCut = $product->getData('incstores_pim_min_rollcut');

            if ($calculatorType == 'roll' && $exactLength && $exactWidth && $minRollCut) {
                $lengthWidthCalculation = ($exactLength * $exactWidth) / 144;
                $finalValue = $lengthWidthCalculation * $itemPerBox * $minOrderQty * $minRollCut;
            } elseif ($calculatorType != 'roll' && $exactLength && $exactWidth) {
                $lengthWidthCalculation = ($exactLength * $exactWidth) / 144;
                $finalValue = $lengthWidthCalculation * $itemPerBox * $minOrderQty;
            } else {
                $finalValue = $itemPerBox * $minOrderQty;
            }

            return round($finalValue, 2);
        } catch (\Exception $e) {
            $this->logger->error('Error computing reference quantity: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * For configurable parents, PIM may store "{childProductId}_{inches}" on exact length/width; return inches.
     */
    private function resolvePimExactDimensionInches(Product $product, $raw): float
    {
        if ($raw === null || $raw === '') {
            return 0.0;
        }
        if ($product->getTypeId() !== ConfigurableType::TYPE_CODE) {
            return (float)$raw;
        }
        if (is_string($raw) && strpos($raw, '_') !== false) {
            $parts = explode('_', $raw, 2);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                return (float)$parts[1];
            }
        }

        return (float)$raw;
    }

    public function loadProductBySku($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            return $product;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info('Product with SKU ' . $sku . ' not found: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->logger->info('An error occurred while loading product by SKU: ' . $e->getMessage());
            return null;
        }
    }

    public function getCalculatedPrice($productId, $priceType = 'final')
    {
        return $this->advanceSearchData->getCalculatedPrice($productId, $priceType);
    }

    public function getCalculatedPriceForProduct(Product $product, $priceType = 'final'): array
    {
        return $this->advanceSearchData->getCalculatedPriceForProduct($product, $priceType);
    }
}
