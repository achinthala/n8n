<?php

declare(strict_types=1);

namespace Dcw\IncstoreShipping\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;


class Data implements ArgumentInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly PricingHelper $pricingHelper
    ) {
    }

    /**
     * function name: calculateUnitWeight
     * @param sku $sku
     */
    public function calculateUnitWeight($item)
    {
        $customLength = "";
        $unitWeight = false;
        try {
            // Load the product by SKU
            $product = $this->productRepository->get($item->getSku());
            // Get the attribute frontend value
            $attributeFrontendValue = $product->getResource()
                                            ->getAttribute('incstores_pim_calculator_type')
                                            ->getFrontend()
                                            ->getValue($product);
            
            if ($attributeFrontendValue == 'roll') {
                $exactLengthInches = $product->getData('incstores_pim_exact_length_inches');

                if ($exactLengthInches && is_numeric($exactLengthInches)) {
                    $exactLengthInFeets = $exactLengthInches / 12; //convert inches to feet
                } else {
                    $exactLengthInFeets = 1;
                }
                
                $options = $item->getOptions();
                $additionalOptions = $item->getOptionByCode('additional_options');

                if ($additionalOptions) {
                    $additionalOptions = $additionalOptions->getValue();
                    $additionalOptionsValue = json_decode($additionalOptions, true);

                    if (isset($additionalOptionsValue['custom_length'])) {
                        $customLength = $additionalOptionsValue['custom_length']['value'];
                    }

                    if (isset($additionalOptionsValue['room_length'])) {
                        $customLength = $additionalOptionsValue['room_length']['value'];
                    }
                }

                $productWeight = (float) $product->getWeight();

                if ($customLength !== '' && is_numeric($customLength)) {
                    $unitWeight = (float) $customLength * $exactLengthInFeets * $productWeight;
                } else {
                    $unitWeight = $exactLengthInFeets * $productWeight;
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        }

        return $unitWeight;
    }

    /**
     * function name: loadProductBySku
     * @param sku $sku
     */
    public function loadProductBySku($sku)
    {
        $product = false;

        try {
            // Load the product by SKU
            $product = $this->productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        }

        return $product;
    }

    /**
     * function name: getAttributeCodeByOptionId
     * @param optionId $optionId
     */
    public function getAttributeCodeByOptionId($optionId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        
        // Load the option details
        $attributeOption = $this->attributeRepository->get('catalog_product', $optionId, $storeId);
        
        if ($attributeOption) {
            return $attributeOption->getAttributeCode();
        }

        return null;
    }

    public function basePriceCalculate($coverageArea, $width, $length, $floorCalculator, $productPrice)
    {
        if (($floorCalculator != '' && $coverageArea > 0) || ($floorCalculator != '' && $width > 0 && $length > 0) && $floorCalculator != 'none') {
                    
            $minSquarfeet = 0;

            if($floorCalculator == 'case') {
                $minSquarfeet = $coverageArea;
            } else if (is_numeric($width) && is_numeric($length)) {
                $width = (float)$width / 12;
                $length = (float)$length / 12;
                $minSquarfeet = (float)$width * (float)$length;
            }

            if($minSquarfeet > 0 && $productPrice > 0 ) {
                return (float)$productPrice/(float)$minSquarfeet;
            }
        }

        return $this->pricingHelper->currency($productPrice, false, false);
    }

    public function formatedPrice($price)
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    public function isCartPage()
    {
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (strpos($httpReferer, 'checkout/cart') !== false) {
            return true;
        }

        return false;
    }

    public function isCheckoutPage()
    {
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (strpos($httpReferer, 'checkout') !== false && strpos($httpReferer, 'cart') === false) {
            return true;
        }

        return false;
    }

	public function isPayPalUrl()
    {
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (strpos($httpReferer, 'paypal') !== false) {
            return true;
        }

        return false;
    }

	public function isSplitpaymentUrl()
    {
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (strpos($httpReferer, 'splitpayment') !== false) {
            return true;
        }

        return false;
    }

    public function isAmazonPayUrl()
    {
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (strpos($httpReferer, 'amazon') !== false) {
            return true;
        }

        return false;
    }

	public function getPageUrl()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }
	
	public function isAmastyQuote()
    {
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (strpos($httpReferer, 'amasty_quote') !== false) {
            return true;
        }

        return false;
    }
	public function isOrderCreateFromAdmin()
    {
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (strpos($httpReferer, 'order_create') !== false) {
            return true;
        }

        return false;
    }
}
