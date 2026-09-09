<?php
declare(strict_types=1);

namespace Dcw\Checkout\Model\Checkout;

use Dcw\Checkout\ViewModel\Data as CheckoutViewModel;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShippingViewModelData;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private readonly CheckoutViewModel $checkoutViewModel,
        private readonly IncstoreShippingViewModelData $incstoreShippingViewModelData,
        private readonly CheckoutSession $checkoutSession
    )
    {
    }
    /**
     * Return savings configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $getsavings = $this->getsavings();

        return [
            'savings' => $getsavings
        ];
    }

    public function getsavings()
    {
        $getCbcProducData = $this->checkoutViewModel->getCbcProducData();
        $totalCbcBasePrice = $totalBasePrice = 0;

        foreach ($getCbcProducData as $cbcFullData => $cbcItems) {
            foreach ($cbcItems as $key => $cbcItem) {
                $totalCbcBasePrice+= $cbcItem['rowBaseTotal'] - $cbcItem['rowTotal'];
            }
        }

        $cartItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
        
        foreach ($cartItems as $item) {
            if (!$this->checkoutViewModel->checkIsCBC($item->getSku())) {
                $loadProduct = $this->incstoreShippingViewModelData->loadProductBySku($item->getSku());

                $getCalculatorType = $loadProduct->getAttributeText('incstores_pim_calculator_type');
                $productPrice = (float)$loadProduct->getFinalPrice();
                $productTierPrice = (float)$loadProduct->getFinalPrice(1);
                $width = $loadProduct->getData('incstores_pim_exact_width_inches');
                $length = $loadProduct->getData('incstores_pim_exact_length_inches');
                $coverageArea = $loadProduct->getIncstoresPimCoverage();

                $basePriceCalculate = $this->incstoreShippingViewModelData->basePriceCalculate($coverageArea, $width, $length, $getCalculatorType, $productPrice);
                $tierPriceCalculate = $this->incstoreShippingViewModelData->basePriceCalculate($coverageArea, $width, $length, $getCalculatorType, $productTierPrice);
                
                if ($basePriceCalculate > 0) {
                    $savePercent = round((($basePriceCalculate - $tierPriceCalculate) / ($basePriceCalculate)) * 100);
                    $cutPrice = $item->getRowTotal() / (1 - ($savePercent / 100));
                    $getRowTotal = $item->getRowTotal();
                    $totalBasePrice+= $cutPrice - $getRowTotal;
                }
            }
        }

        return $totalCbcBasePrice + $totalBasePrice;
    }
}
