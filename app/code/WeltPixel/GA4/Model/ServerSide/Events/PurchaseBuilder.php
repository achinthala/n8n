<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterface;
use WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\PurchaseItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class PurchaseBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface
{
    /**
     * @var PurchaseInterfaceFactory
     */
    protected $purchaseFactory;

    /**
     * @var PurchaseItemInterfaceFactory
     */
    protected $purchaseItemFactory;

    /**
     * @var GA4Helper
     */
    protected $ga4Helper;

    /**
     * @var DimensionModel
     */
    protected $dimensionModel;

    /**
     * @param PurchaseInterfaceFactory $purchaseFactory
     * @param PurchaseItemInterfaceFactory $purchaseItemFactory
     * @param GA4Helper $ga4Helper
     * @param DimensionModel $dimensionModel
     */
    public function __construct(
        PurchaseInterfaceFactory $purchaseFactory,
        PurchaseItemInterfaceFactory $purchaseItemFactory,
        GA4Helper $ga4Helper,
        DimensionModel $dimensionModel
    )
    {
        $this->purchaseFactory = $purchaseFactory;
        $this->purchaseItemFactory = $purchaseItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->dimensionModel = $dimensionModel;
    }

    /**
     * @param $order
     * @return null|PurchaseInterface
     */
    function getPurchaseEvent($order)
    {
        /** @var PurchaseInterface $purchaseEvent */
        $purchaseEvent = $this->purchaseFactory->create();

        if (!$order) {
            return $purchaseEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $purchaseEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation(false);
        $clientId = $order->getData('ga_cookie');
        $gaSessionId = $order->getData('ga_session_id');
        $gaTimestamp = $order->getData('ga_timestamp');
        $userId = $order->getCustomerId();

        $currencyCode = $order->getOrderCurrencyCode();

        $purchaseEvent->setPageLocation($pageLocation);
        $purchaseEvent->setClientId($clientId);
        if ($gaSessionId) {
            $purchaseEvent->setSessionId($gaSessionId);
        }
        if ($gaTimestamp) {
            $purchaseEvent->setTimestamp($gaTimestamp);
        }
        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $purchaseEvent->setUserId($userId);
        }
        $purchaseEvent->setTransactionId($order->getIncrementId());
        $purchaseEvent->setCoupon((string)$order->getCouponCode());
        $purchaseEvent->setValue(floatval(number_format($this->getOrderTotal($order), 2, '.', '')));
        $purchaseEvent->setShipping(floatval(number_format($order->getShippingAmount(), 2, '.', '')));
        $purchaseEvent->setTax(floatval(number_format($order->getTaxAmount(), 2, '.', '')));
        $purchaseEvent->setCurrency($currencyCode);

        $displayOption = $this->ga4Helper->getParentOrChildIdUsage();

        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIdModel = $product;
            if ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildrenItems();
                    foreach ($children as $child) {
                        $productIdModel = $child->getProduct();
                    }
                }
            }

            $productItemOptions = [];
            $productItemOptions['item_name'] = html_entity_decode($productIdModel->getName() ?? '');
            $productItemOptions['item_id'] = $this->ga4Helper->getGtmProductId($productIdModel); //$this->helper->getGtmOrderItemId($item);
            $productItemOptions['affiliation'] = $this->ga4Helper->getAffiliationName();
            $productItemOptions['price'] = floatval(number_format($item->getPrice(), 2, '.', ''));
            if ($this->ga4Helper->isBrandEnabled()) {
                $productItemOptions['item_brand'] = $this->ga4Helper->getGtmBrand($product);
            }
            if ($this->ga4Helper->isVariantEnabled()) {
                $productOptions = $item->getData('product_options');
                $productType = $item->getData('product_type');
                $variant = $this->ga4Helper->checkVariantForProductOptions($productOptions, $productType);
                if ($variant) {
                    $productItemOptions['item_variant'] = $variant;
                }
            }
            $productCategoryIds = $product->getCategoryIds();
            $categoryName = $this->ga4Helper->getGtmCategoryFromCategoryIds($productCategoryIds);
            $ga4Categories = $this->ga4Helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
            $productItemOptions = array_merge($productItemOptions, $ga4Categories);
            $productItemOptions['item_list_name'] = $categoryName;
            $productItemOptions['item_list_id'] = count($productCategoryIds) ? $productCategoryIds[0] : '';
            $productItemOptions['quantity'] = (double)$item->getQtyOrdered();
            $productDiscountedSpecialPrice = $item->getOriginalPrice() - $item->getPrice();
            if ($item->getDiscountAmount() > 0 || ($productDiscountedSpecialPrice > 0) ) {
                $discountValuePerItem = $item->getDiscountAmount() / $item->getQtyOrdered()  + ($productDiscountedSpecialPrice);
                $productItemOptions['discount'] = floatval(number_format($discountValuePerItem, 2, '.', ''));
                $productItemOptions['price'] = floatval(number_format($item->getOriginalPrice() ? ($item->getOriginalPrice() - $discountValuePerItem) : 0, 2, '.', ''));
            }

            /**  Set the custom dimensions */
            $customDimensions = $this->dimensionModel->getProductDimensions($product, $this->ga4Helper);
            foreach ($customDimensions as $name => $value) :
                $productItemOptions[$name] = $value;
            endforeach;

            $purchaseItem = $this->purchaseItemFactory->create();
            $purchaseItem->setParams($productItemOptions);

            $purchaseEvent->addItem($purchaseItem);

        }

        return $purchaseEvent;
    }

    /**
     * Retuns the order total (subtotal or grandtotal)
     * @return float
     */
    protected function getOrderTotal($order)
    {
        $orderTotalCalculationOption = $this->ga4Helper->getOrderTotalCalculation();
        switch ($orderTotalCalculationOption) {
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_SUBTOTAL:
                $orderTotal = $order->getSubtotal();
                break;
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_GRANDTOTAL:
            default:
                $orderTotal = $order->getGrandtotal();
                if ($this->ga4Helper->excludeTaxFromTransaction()) {
                    $orderTotal -= $order->getTaxAmount();
                }

                if ($this->ga4Helper->excludeShippingFromTransaction()) {
                    $orderTotal -= $order->getShippingAmount();
                    if ($this->ga4Helper->excludeShippingFromTransactionIncludingTax()) {
                        $orderTotal -= $order->getShippingTaxAmount();
                    }
                }
                break;
        }

        return $orderTotal;
    }
}
