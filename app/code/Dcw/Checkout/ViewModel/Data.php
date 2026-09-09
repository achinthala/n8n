<?php

declare(strict_types=1);

namespace Dcw\Checkout\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShippingViewModelData;
use Magento\Quote\Model\QuoteRepository;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Data implements ArgumentInterface
{
    const XML_PATH_TRACKING_LINKS = 'dcw_shipping_tracking/shipping_tracking_config/shipping_tracking_links';

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var IncstoreShippingViewModelData
     */
    protected IncstoreShippingViewModelData $incstoreShippingViewModelData;

    /**
     * @var QuoteRepository
     */
    protected QuoteRepository $quoteRepository;

    /**
     * @var AttributeRepositoryInterface
     */
    protected AttributeRepositoryInterface $attributeRepository;

    /**
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckoutSession $checkoutSession
     * @param IncstoreShippingViewModelData $incstoreShippingViewModelData
     * @param QuoteRepository $quoteRepository
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        IncstoreShippingViewModelData $incstoreShippingViewModelData,
        QuoteRepository $quoteRepository,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->incstoreShippingViewModelData = $incstoreShippingViewModelData;
        $this->quoteRepository = $quoteRepository;
        $this->attributeRepository = $attributeRepository;
    }

    public function getFrontName(): string
    {
        return $this->request->getModuleName(); // This gets the front name (module name)
    }

    public function getFullControllerName(): string
    {
        $frontName = $this->request->getModuleName();
        $controller = $this->request->getControllerName();
        $action = $this->request->getActionName();

        return $frontName . '/' . $controller . '/' . $action;
    }

    public function getTrackingLinks(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_LINKS,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCbcProducData(): array
    {
        $cartItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $cbcData = [];
        foreach ($cartItems as $item) {
            if ($this->checkIsCBC($item->getSku())) {
                $lastUnderscorePos = strrpos($item->getSku(), "_");
                if ($lastUnderscorePos) {
                    $cbcSku = $item->getSku();
                    $cbcMainSku = substr($item->getSku(), 0, $lastUnderscorePos);
                    $cbcType = substr($item->getSku(), $lastUnderscorePos + 1);

                    $loadProduct = $this->incstoreShippingViewModelData->loadProductBySku($item->getSku());

                    $getCalculatorType = $loadProduct->getAttributeText('incstores_pim_calculator_type');
                    $productPrice = (float)$loadProduct->getFinalPrice();
                    $productTierPrice = (float)$loadProduct->getFinalPrice(1);
                    $width = $loadProduct->getData('incstores_pim_exact_width_inches');
                    $length = $loadProduct->getData('incstores_pim_exact_length_inches');
                    $coverageArea = $loadProduct->getIncstoresPimCoverage();

                    $basePriceCalculate = $this->incstoreShippingViewModelData->basePriceCalculate($coverageArea, $width, $length, $getCalculatorType, $productPrice);
                    $tierPriceCalculate = $this->incstoreShippingViewModelData->basePriceCalculate($coverageArea, $width, $length, $getCalculatorType, $productTierPrice);
                    $cutPrice = "";

                    if ($basePriceCalculate > 0) {
                        $savePercent = round((($basePriceCalculate - $tierPriceCalculate) / ($basePriceCalculate)) * 100);
                        $cutPrice = $item->getRowTotal() / (1 - ($savePercent / 100));
                    }

                    $cbcData[$cbcMainSku][$cbcSku]['sku'] = $cbcSku;
                    $cbcData[$cbcMainSku][$cbcSku]['cbcMainSku'] = $cbcMainSku;
                    $cbcData[$cbcMainSku][$cbcSku]['cbcType'] = $cbcType;
                    $cbcData[$cbcMainSku][$cbcSku]['itemId'] = $item->getId();
                    $cbcData[$cbcMainSku][$cbcSku]['qty'] = $item->getQty();
                    $cbcData[$cbcMainSku][$cbcSku]['weight'] = $item->getWeight() * $item->getQty();
                    $cbcData[$cbcMainSku][$cbcSku]['rowTotal'] = $item->getRowTotal();
                    $cbcData[$cbcMainSku][$cbcSku]['rowBaseTotal'] = $cutPrice;
                    $cbcData[$cbcMainSku][$cbcSku]['savePercent'] = $savePercent;
                }
            }
        }

        return $cbcData;
    }

    public function checkIsCBC(string $sku): bool
    {
        if ($sku) {
            $parts = explode('_', $sku);
            $getProductCbcType = end($parts);
            $cbsTypesArray = ['center', 'border', 'corner'];
            if (in_array($getProductCbcType, $cbsTypesArray)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Remove grouped line items from cart based on a SKU pattern
     *
     * @param int $quoteItemId
     * @return void
     */
    public function removeGroupedItems(int $quoteItemId)
    {
        $quote = $this->checkoutSession->getQuote();
        $targetItem = $quote->getItemById((int) $quoteItemId);

        if ($targetItem) {
            $sku = $targetItem->getSku();
            if (!empty($sku) && $this->checkIsCBC($sku)) {
                $lastUnderscorePos = strrpos($sku, "_");
                if ($lastUnderscorePos) {
                    $cbcMainSku = substr($sku, 0, $lastUnderscorePos);
                    $quoteItems = $quote->getAllVisibleItems();
                    // Loop through all cart items and remove items matching the same pattern
                    foreach ($quoteItems as $item) {
                        if (str_starts_with($item->getSku(), $cbcMainSku . '_')) {
                            $quote->removeItem($item->getItemId());
                        }
                    }
                }
            } else {
                $quote->removeItem($quoteItemId);
            }
        } else {
            $quote->removeItem($quoteItemId);
        }

        // Save updated quote
        $quote->setTotalsCollectedFlag(false)->collectTotals();
        $this->quoteRepository->save($quote);
    }

    public function getAttributeCodeById(int $attributeId, string $entityType = 'catalog_product'): ?string
    {
        try {
            $attribute = $this->attributeRepository->get($entityType, $attributeId);
            return $attribute->getAttributeCode();
        } catch (NoSuchEntityException $e) {
            return null; // Attribute ID not found
        }
    }
}
