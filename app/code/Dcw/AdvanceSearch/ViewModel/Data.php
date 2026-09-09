<?php

declare(strict_types=1);

namespace Dcw\AdvanceSearch\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class Data implements ArgumentInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * function name: calculateUnitWeight
     * @param int|string $productId Product ID
     * @param string $priceType
     * @return array
     */
    public function getCalculatedPrice($productId, $priceType = 'final')
    {
        try {
            $product = $this->productRepository->getById($productId);
            return $this->getCalculatedPriceForProduct($product, $priceType);
        } catch (\Exception $e) {
            $this->logger->info('An error occurred in getCalculatedPrice: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get calculated price for a product object (optimized version)
     * 
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $priceType
     * @return array
     */
    public function getCalculatedPriceForProduct($product, $priceType = 'final')
    {
        $calculatePriceArray = [];

        try {
            if (!$product || !$product->getId()) {
                return $calculatePriceArray;
            }
            if ($priceType == 'final') {
                $productPrice = (float)$product->getFinalPrice(1);
            } else {
                $productPrice = (float)$product->getFinalPrice();
            }

            $width = $product->getData('incstores_pim_exact_width_inches');
            $length = $product->getData('incstores_pim_exact_length_inches');
            $coverageArea = $product->getIncstoresPimCoverage();
            $floorCalculator = $product->getAttributeText('incstores_pim_calculator_type');
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

            $floorCalculator = $product->getAttributeText('incstores_pim_calculator_type');
            $altUnitOfMeasure = $product->getAttributeText('incstores_pim_alt_unit_of_measure');
            if ($altUnitOfMeasure == "") {
                $altUnitOfMeasure = "sqft";
            }

            if ($minPriceChildProductId) {
                $minPriceChildProduct  = $this->productRepository->getById($minPriceChildProductId);;
                $floorCalculator = $minPriceChildProduct->getAttributeText('incstores_pim_calculator_type');
                $altUnitOfMeasure = $minPriceChildProduct->getAttributeText('incstores_pim_alt_unit_of_measure');
                $coverageArea = $minPriceChildProduct->getIncstoresPimCoverage();

                if ($altUnitOfMeasure == "") {
                    $altUnitOfMeasure = "sqft";
                }

                if ($priceType == 'final') {
                    $productPrice = (float)$minPriceChildProduct->getFinalPrice(1);
                } else {
                    $productPrice = (float)$minPriceChildProduct->getFinalPrice();
                }

                $floorCalculatorArray = ['case', 'roll', 'sqft'];

                $calType = 'each';
                
                if (isset($floorCalculator) && in_array($floorCalculator, $floorCalculatorArray)) {
                    $calType = 'sqft';
                }

                if (isset($floorCalculator) && $floorCalculator == 'none') {
                    $calType = 'none';
                }

                if (isset($floorCalculator) && $floorCalculator == 'pre_cut_roll') {
                    $calType = $altUnitOfMeasure;
                }

                if ($calType !== '' && $calType !== 'none' && ($coverageArea > 0 || ($width > 0 && $length > 0))) {
                    $minSquarfeet = 0;

                    if($floorCalculator == 'case') {
                        $minSquarfeet = $coverageArea;
                    } else if (is_numeric($width) && is_numeric($length)) {
                        $width = (float)$width / 12;
                        $length = (float)$length / 12;
                        $minSquarfeet = $width * $length;
                    }

                    if($minSquarfeet > 0 && $productPrice > 0 ) {
                        $productPrice = (float)$productPrice/(float)$minSquarfeet;
                    }
                }

                if ($coverageArea == '' || $coverageArea < 1) {
                    $coverageArea = 1;
                }

                if (isset($floorCalculator) && $floorCalculator == 'pre_cut_roll' && $coverageArea > 0) {
                    if ($priceType == 'final') {
                        $productPrice = (float)$minPriceChildProduct->getFinalPrice(1);
                    } else {
                        $productPrice = (float)$minPriceChildProduct->getFinalPrice();
                    }
                    $productPrice = (float)$productPrice / (float)$coverageArea;
                }

                $productPrice = number_format($productPrice, 2, '.', '');
                $calculatePriceArray['price'] = $productPrice;
                $calculatePriceArray['calType'] = $calType;
                if ($calType == 'none') {
                    $calculatePriceArray['calType'] = 'each';
                }
            } else {
                $calType = 'each';

                if ($floorCalculator == "") {
                    $calculatePriceArray['calType'] = $calType;
                    $productPrice = $this->priceCalculate($calType, $coverageArea, $width, $length, $floorCalculator, $productPrice);
                } elseif ($floorCalculator != 'none') {
                    $floorCalculatorArray = ['case', 'roll', 'sqft'];

                    $calType = 'each';
                    
                    if (isset($floorCalculator) && in_array($floorCalculator, $floorCalculatorArray)) {
                        $calType = 'sqft';
                    }

                    if (isset($floorCalculator) && $floorCalculator == 'pre_cut_roll') {
                        $calType = $altUnitOfMeasure;
                    }

                    if ($coverageArea == '' || $coverageArea < 1) {
                        $coverageArea = 1;
                    }

                    if (isset($floorCalculator) && $floorCalculator == 'pre_cut_roll' && $coverageArea > 0) {
                        if ($priceType == 'final') {
                            $productPrice = (float)$product->getFinalPrice(1);
                        } else {
                            $productPrice = (float)$product->getFinalPrice();
                        }

                        $productPrice = (float)$productPrice / (float)$coverageArea;

                        $productPrice = number_format($productPrice, 2, '.', '');
                        $calculatePriceArray['price'] = $productPrice;
                        $calculatePriceArray['calType'] = $calType;
                        
                        return $calculatePriceArray;
                    }

                    $calculatePriceArray['calType'] = $calType;
                    $productPrice = $this->priceCalculate($calType, $coverageArea, $width, $length, $floorCalculator, $productPrice);
                } else {
                    $calculatePriceArray['calType'] = $floorCalculator;
                    if ($floorCalculator == 'none') {
                        $calculatePriceArray['calType'] = 'each';
                    }
                    if ($priceType == 'final') {
                        $productPrice = (float)$product->getFinalPrice(1);
                    } else {
                        $productPrice = (float)$product->getFinalPrice();
                    }
                }

                $productPrice = number_format($productPrice, 2, '.', '');
                $calculatePriceArray['price'] = $productPrice;
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        }

        return $calculatePriceArray;
    }

    public function priceCalculate($calType, $coverageArea, $width, $length, $floorCalculator, $productPrice)
    {
        if ($calType !== '' && $calType !== 'none' && ($coverageArea > 0 || ($width > 0 && $length > 0))) {
                    
            $minSquarfeet = 0;

            if($floorCalculator == 'case') {
                $minSquarfeet = $coverageArea;
            } else if (is_numeric($width) && is_numeric($length)) {
                $width = (float)$width / 12;
                $length = (float)$length / 12;
                $minSquarfeet = $width * $length;
            }

            if($minSquarfeet > 0 && $productPrice > 0 ) {
                return (float)$productPrice/(float)$minSquarfeet;
            }
        }

        return $productPrice;
    }
}
