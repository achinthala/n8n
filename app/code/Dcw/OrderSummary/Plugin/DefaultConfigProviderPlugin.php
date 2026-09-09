<?php

namespace Dcw\OrderSummary\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Dcw\IncstoreShipping\ViewModel\Data;
use Dcw\Checkout\ViewModel\Data as CheckoutData;
use Magento\Framework\View\LayoutInterface;

class DefaultConfigProviderPlugin
{

    /**
     *@var checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var Data
     */
    protected $incstoreShippingViewModelData;

    /**
     * @var CheckoutData
     */
    protected $checkoutViewModel;

    /**
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     *Constructor
     * @param CheckoutSession $checkoutSession
     * @param Data $incstoreShippingViewModelData
     * @param CheckoutData $checkoutViewModel
     * @param LayoutInterface $layout
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Data $incstoreShippingViewModelData,
        CheckoutData $checkoutViewModel,
        LayoutInterface $layout
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->incstoreShippingViewModelData = $incstoreShippingViewModelData;
        $this->checkoutViewModel = $checkoutViewModel;
        $this->_layout = $layout;
    }

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result)
    {
        if (isset($result['totalsData']['items'])) {
            foreach ($result['totalsData']['items'] as $index => &$item) {
                $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);
                $getPdpLineItem = $quoteItem->getPdpLineItem();
                $shippingEstimate = "";
                if ($getPdpLineItem) {
                    $getPdpLineItem = json_decode($getPdpLineItem, true);
                    $shippingEstimate = $getPdpLineItem['shipping_estimate'] ?? '';
                }
                $loadChildProduct = $this->incstoreShippingViewModelData->loadProductBySku($quoteItem->getSku());
                $getCalculatorType = $loadChildProduct->getAttributeText('incstores_pim_calculator_type');

                $isCbcProduct = $this->checkoutViewModel->checkIsCBC($quoteItem->getSku());
                $totalCbcWeight = $totalCbcPrice = $totalCbcBasePrice = $totalCbcSavePercent = 0;
                $parentCbcProductLoad = "";
                $cbcProductMatch = "";

                if ($isCbcProduct) {
                    // Find the position of the first underscore
                    $underscorePos = strpos($quoteItem->getSku(), "_");

                    if ($underscorePos !== false) {
                        $parentCbcProduct = substr($quoteItem->getSku(), 0, $underscorePos);
                        $parentCbcProductLoad = $this->incstoreShippingViewModelData->loadProductBySku($parentCbcProduct);
                    }

                    $lastUnderscorePos = strrpos($quoteItem->getSku(), "_");
                    $cbcProductMatch = substr($quoteItem->getSku(), 0, $lastUnderscorePos);
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
                $items = $quoteItem;
                if (!$isCbcProduct && $getCalculatorType == 'roll') {
                    $unitWeight = $this->incstoreShippingViewModelData->calculateUnitWeight($items);

                    if ($unitWeight === false) {
                        $unitWeight = 0;
                    }

                    $getProductWeight = $unitWeight * $items->getQty();
                }

                if (!$getProductWeight) {
                    $getProductWeight = $items->getWeight() * $items->getQty();
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

                    $cutPrice = $items->getRowTotal() / (1 - ($savePercent / 100));
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

                $result['quoteItemData'][$index]['cutPrice'] = ($this->incstoreShippingViewModelData->formatedPrice($items->getRowTotal()) == $cutPrice) ? '' : $cutPrice;
                $result['quoteItemData'][$index]['stock_status'] = $quoteItem->getProduct()->isInStock() ? 'In Stock' : 'Out of Stock';
                $result['quoteItemData'][$index]['discount_percentage'] = $savePercentHtml;
                $result['quoteItemData'][$index]['calculated_square_feet_price'] = $productCalculatedPrice . $calculatorType;
                $result['quoteItemData'][$index]['shipping_estimate'] = $shippingEstimate;
                $result['quoteItemData'][$index]['product_weight'] = $getProductWeightHtml;
            }

            $orderSummaryStaticBlock = $this->_layout->createBlock('Magento\Cms\Block\Block')
                ->setBlockId("got_a_question")
                ->toHtml();
            $result['cms_block'] = $orderSummaryStaticBlock;
        }
        return $result;
    }
}
