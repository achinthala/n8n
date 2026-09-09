<?php

declare(strict_types=1);

namespace Dcw\ProductImage\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Helper\Image as ImageHelper;
use Dcw\ProductImage\Helper\Data as ProductImageHelper;
use Magento\Catalog\Block\Product\ListProduct;
use Dcw\Checkout\ViewModel\Data as CheckoutData;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShipping;
use Exception;

class DefaultItem
{
	protected $productRepo;
	protected $imageHelper;
	protected $dcwImageHelper;
	protected $listProductBlock;

    /**
     * @var IncstoreShipping
     */
    protected $incstoreShippingViewModelData;

    /**
     *
     * @var CheckoutData
     */
    private $checkoutViewModel;

	public function __construct(
		ProductRepositoryInterface $productRepository,
		ImageHelper $imageHelper,
		ProductImageHelper $dcwImageHelper,
		ListProduct $listProductBlock,
        CheckoutData $checkoutViewModel,
        IncstoreShipping $incstoreShippingViewModelData,
	) {
		$this->productRepo = $productRepository;
		$this->imageHelper = $imageHelper;
		$this->dcwImageHelper = $dcwImageHelper;
		$this->listProductBlock = $listProductBlock;
        $this->checkoutViewModel = $checkoutViewModel;
        $this->incstoreShippingViewModelData = $incstoreShippingViewModelData;
	}

	public function aroundGetItemData($subject, \Closure $proceed, Item $item)
	{
		$data = $proceed($item);
		$productType = $item->getProductType();

		if ($productType == 'configurable') {
			$simpleProductOption = $item->getProduct()->getCustomOption('simple_product');
			if ($simpleProductOption && $simpleProductOption->getProduct()) {
				$simpleProduct = $simpleProductOption->getProduct();
				$productId = $simpleProduct->getId();
				$data['simple_product_id'] = $productId;
			} else {
				$productId = $item->getProduct()->getId();
			}
		} else {
			$productId = $item->getProduct()->getId();
		}


        $getPdpLineItem = $item->getPdpLineItem();
        $shippingEstimate = "";

        if ($getPdpLineItem) {
            $getPdpLineItem = json_decode($getPdpLineItem, true);
            $shippingEstimate = $getPdpLineItem['shipping_estimate'] ?? '';
        }

        $loadChildProduct = $this->incstoreShippingViewModelData->loadProductBySku($item->getSku());
        $getCalculatorType = $loadChildProduct->getAttributeText('incstores_pim_calculator_type');

        $isCbcProduct = $this->checkoutViewModel->checkIsCBC($item->getSku());
        $totalCbcWeight = $totalCbcPrice = $totalCbcBasePrice = $totalCbcSavePercent = 0;
        $parentCbcProductLoad = "";
        $cbcProductMatch = "";

        if ($isCbcProduct) {
            // Find the position of the first underscore
            $underscorePos = strpos($item->getSku(), "_");

            if ($underscorePos !== false) {
                $parentCbcProduct = substr($item->getSku(), 0, $underscorePos);
                $parentCbcProductLoad = $this->incstoreShippingViewModelData->loadProductBySku($parentCbcProduct);
                $getCalculatorType = $loadChildProduct->getAttributeText('incstores_pim_calculator_type');
            }

            $lastUnderscorePos = strrpos($item->getSku(), "_");
            $cbcProductMatch = substr($item->getSku(), 0, $lastUnderscorePos);
            $cartCbcData = $this->checkoutViewModel->getCbcProducData();
            $cbcProductCount = 0;

            foreach ($cartCbcData as $cbcFullData => $cbcItems) {
                foreach ($cbcItems as $key => $cbcItem) {
                    if ($cbcProductMatch == $cbcFullData) {
                        $totalCbcWeight += $cbcItem['weight'];
                        $totalCbcPrice += $cbcItem['rowTotal'];
                        $totalCbcBasePrice += $cbcItem['rowBaseTotal'];
                        $totalCbcSavePercent += $cbcItem['savePercent'];
                        $cbcProductCount++;
                    }
                }
            }
        }

        $getProductWeight = 0;
        if (!$isCbcProduct && $getCalculatorType == 'roll') {
            $unitWeight = $this->incstoreShippingViewModelData->calculateUnitWeight($item);

            if ($unitWeight === false) {
                $unitWeight = 0;
            }

            $getProductWeight = $unitWeight * $item->getQty();
        }

        if (!$getProductWeight) {
            $getProductWeight = $item->getWeight() * $item->getQty();
        }

        if ($isCbcProduct && $totalCbcWeight > 0) {
            $getProductWeight = $totalCbcWeight;
        }

        $getProductWeightHtml = "";

        if ($getProductWeight > 0) {
            $getProductWeightHtml = " ($getProductWeight lbs)";
        }

        $productPrice = (float)$loadChildProduct->getPrice();
        $productTierPrice = (float)$loadChildProduct->getFinalPrice(1);
        $width = $loadChildProduct->getData('incstores_pim_exact_width_inches');
        $length = $loadChildProduct->getData('incstores_pim_exact_length_inches');
        $coverageArea = $loadChildProduct->getIncstoresPimCoverage();

        $basePriceCalculate = $this->incstoreShippingViewModelData->basePriceCalculate($coverageArea, $width, $length, $getCalculatorType, $productPrice);
        $tierPriceCalculate = $this->incstoreShippingViewModelData->basePriceCalculate($coverageArea, $width, $length, $getCalculatorType, $productTierPrice);
        $savePercentHtml = $cutPrice = "";
        $productCalculatedPrice = 0;

        if ($basePriceCalculate > 0) {
            $savePercent = round((($basePriceCalculate - $tierPriceCalculate) / ($basePriceCalculate)) * 100);
            if ($savePercent > 0) {
                $savePercentHtml = 'Save ' . $savePercent . '%';
            }

            $cutPrice = $item->getRowTotal() / (1 - ($savePercent / 100));
            $cutPrice = $this->incstoreShippingViewModelData->formatedPrice($cutPrice);

            $productCalculatedPrice = $this->incstoreShippingViewModelData->formatedPrice($tierPriceCalculate);
        }

        if ($totalCbcSavePercent > 0) {
            $savePercent = $totalCbcSavePercent / $cbcProductCount;
            if ($savePercent > 0) {
                $savePercentHtml = 'Save ' . $savePercent . '%';
            }
        }

        if ($totalCbcBasePrice > 0) {
            $cutPrice = $this->incstoreShippingViewModelData->formatedPrice($totalCbcBasePrice);
        }
        $floorCalculatorArray = ['case', 'roll', 'sqft'];
        $calculatorType = '';

        if (isset($getCalculatorType) && in_array($getCalculatorType, $floorCalculatorArray)) {
            $calculatorType = ' /sqft';
        }

        if (isset($getCalculatorType) && $getCalculatorType == 'pre_cut_roll') {

            $altUnitMeasure = $loadChildProduct->getAttributeText('incstores_pim_alt_unit_of_measure');
            if ($altUnitMeasure) {
                $calculatorType = ' /' . $altUnitMeasure;
            } else {
                $calculatorType = ' /sqft';
            }
        }

        $data['cutPrice'] = ($this->incstoreShippingViewModelData->formatedPrice($item->getRowTotal()) == $cutPrice) ? '' : $cutPrice;
        $data['stock_status'] = $item->getProduct()->isInStock() ? 'In Stock' : 'Out of Stock';
        $data['discount_percentage'] = $savePercentHtml;
        $data['calculated_square_feet_price'] = $productCalculatedPrice . $calculatorType;
        $data['shipping_estimate'] = $shippingEstimate;
        $data['product_weight'] = $getProductWeightHtml;

		$product = $this->productRepo->getById($productId);
		$image = $this->dcwImageHelper->getMainImageUrl($product);
		$optionArray=$data['options'];
		$sampleFlag = 0;

		foreach($optionArray as $key => $value) {
			if($key=="configurable_product_image") {
				$data['product_image']["src"]=$value['value'];
				$data['product_image']["alt"]=$value['alt'];
				$sampleFlag = 1;
			}
		}

		if($sampleFlag == 0) {
			try {
				if ($image!="") {
					$imagesdimensionmedium = $this->dcwImageHelper->getSmallImageDimension();
					$image = $image.'/-B'.$imagesdimensionmedium.'-FWEBP';
					$data['product_image']['src'] = $image;
				} else {
					$placeHolderImage = $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
					$data['product_image']['src'] = $placeHolderImage;
				}
			} catch (Exception $e) {
				//do nothing
			}
		}

		return $data;
	}
}
