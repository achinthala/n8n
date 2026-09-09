<?php
namespace WeltPixel\GA4\Block;

/**
 * Class \WeltPixel\GA4\Block\Order
 */
class Order extends \WeltPixel\GA4\Block\Core
{
    /**
     * @var double
     */
    protected $discountedAmount = 0;

    /**
     * Returns the product details for the purchase gtm event
     * @return array
     */
    public function getProducts()
    {
        $order = $this->getOrder();
        $products = [];

        $displayOption = $this->helper->getParentOrChildIdUsage();

        $this->discountedAmount = 0;

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

            $productDetail = [];
            $productDetail['item_name'] = html_entity_decode($productIdModel->getName() ?? '');
            $productDetail['affiliation'] = $this->helper->getAffiliationName();
            $productDetail['item_id'] = $this->helper->getGtmProductId($productIdModel);
            $productDetail['price'] = floatval(number_format($item->getPrice() ?? 0, 2, '.', ''));
            if ($this->helper->isBrandEnabled()) {
                $productDetail['item_brand'] = $this->helper->getGtmBrand($product);
            }
            if ($this->helper->isVariantEnabled()) {
                $productOptions = $item->getData('product_options');
                $productType = $item->getData('product_type');
                $variant = $this->helper->checkVariantForProductOptions($productOptions, $productType);
                if ($variant) {
                    $productDetail['item_variant'] = $variant;
                }
            }
            $productCategoryIds = $product->getCategoryIds();
            $categoryName = $this->helper->getGtmCategoryFromCategoryIds($productCategoryIds);
            $ga4Categories = $this->helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
            $productDetail = array_merge($productDetail, $ga4Categories);
            $productDetail['item_list_name'] = $categoryName;
            $productDetail['item_list_id'] = count($productCategoryIds) ? $productCategoryIds[0] : '';
            $productDetail['quantity'] = (double)$item->getQtyOrdered();
            $productDiscountedSpecialPrice = $item->getOriginalPrice() - $item->getPrice();
            if ($item->getDiscountAmount() > 0 || ($productDiscountedSpecialPrice > 0) ) {
                $discountValuePerItem = $item->getDiscountAmount() / $item->getQtyOrdered() + ($productDiscountedSpecialPrice);
                $productDetail['discount'] = floatval(number_format($discountValuePerItem, 2, '.', ''));
                $productDetail['price'] = floatval(number_format($item->getOriginalPrice() ? ($item->getOriginalPrice() - $discountValuePerItem) : 0, 2, '.', ''));
                $this->discountedAmount += $discountValuePerItem * $item->getQtyOrdered();
            }

            /**  Set the custom dimensions */
            $customDimensions = $this->getProductDimensions($product);
            foreach ($customDimensions as $name => $value) :
                $productDetail[$name] = $value;
            endforeach;

            $products[] = $productDetail;
        }

        return $products;
    }

    /**
     * @return array
     */
    public function getConversionCartDataItems()
    {
        $order = $this->getOrder();
        $items = [];
        $displayOption = $this->helper->getParentOrChildIdUsage();

        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            if ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildrenItems();
                    foreach ($children as $child) {
                        $product = $child->getProduct();
                    }
                }
            }

            $items[] = [
                'id' => $this->helper->getGtmProductId($product),
                'quantity' => (double)$item->getQtyOrdered(),
                'price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', ''))
            ];
        }

        return $items;
    }

    /**
     * Returns the product id's
     * @return array
     */
    public function getProductIds()
    {
        $order = $this->getOrder();
        $products = [];

        $displayOption = $this->helper->getParentOrChildIdUsage();

        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            if ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildrenItems();
                    foreach ($children as $child) {
                        $product = $child->getProduct();
                    }
                }
            }

            $products[] = $this->helper->getGtmProductId($product); //$this->helper->getGtmOrderItemId($item);
        }

        return $products;
    }

    /**
     * @return bool|string
     */
    public function getAdwordNewCustomer()
    {
        $order =  $this->getOrder();
        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();

        $customerOrderCount = $this->helper->getCustomerOrderCount($customerId, $customerEmail);

        return $customerOrderCount <= 1;
    }

    /**
     * @param $conversionTrackingNewCustomer
     * @return string
     */
    public function getAdwordCustomerLifetimeValue($conversionTrackingNewCustomer)
    {
        if ($conversionTrackingNewCustomer == true) {
            return $this->getOrderTotal();
        }
        return '';
    }

    /**
     * Retuns the order total (subtotal or grandtotal)
     * @return float
     */
    public function getOrderTotal()
    {
        $orderTotalCalculationOption = $this->helper->getOrderTotalCalculation();
        $order =  $this->getOrder();
        switch ($orderTotalCalculationOption) {
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_SUBTOTAL:
                $orderTotal = $order->getSubtotal();
                break;
            case \WeltPixel\GA4\Model\Config\Source\OrderTotalCalculation::CALCULATE_GRANDTOTAL:
            default:
                $orderTotal = $order->getGrandtotal();
                if ($this->excludeTaxFromTransaction()) {
                    $orderTotal -= $order->getTaxAmount();
                }

                if ($this->excludeShippingFromTransaction()) {
                    $orderTotal -= $order->getShippingAmount();
                    if ($this->excludeShippingFromTransactionIncludingTax()) {
                        $orderTotal -= $order->getShippingTaxAmount();
                    }
                }
                break;
        }

        return $orderTotal;
    }

    /**
     * @return bool
     */
    public function isFreeOrderTrackingAllowedForGoogleAnalytics()
    {
        $excludeFreeOrder = $this->helper->excludeFreeOrderFromPurchaseForGoogleAnalytics();
        return $this->isFreeOrderAllowed($excludeFreeOrder);
    }

    /**
     * @param $order
     * @return bool
     */
    public function isOrderTrackingAllowedBasedOnOrderStatus($order)
    {
        return $this->helper->isOrderTrackingAllowedBasedOnOrderStatus($order);
    }

    /**
     * @return bool
     */
    public function isFreeOrderAllowedForAdwordsConversionTracking()
    {
        $excludeFreeOrder = $this->helper->excludeFreeOrderFromAdwordsConversionTracking();
        return $this->isFreeOrderAllowed($excludeFreeOrder);
    }

    /**
     * @return bool
     */
    public function isFreeOrderAllowedForAdwordsRemarketing()
    {
        $excludeFreeOrder = $this->helper->excludeFreeOrderFromAdwordsRemarketing();
        return $this->isFreeOrderAllowed($excludeFreeOrder);
    }

    /**
     * @param bool $excludeFreeOrder
     * @return bool
     */
    protected function isFreeOrderAllowed($excludeFreeOrder)
    {
        if (!$excludeFreeOrder) {
            return true;
        }

        $order = $this->getOrder();
        $orderTotal = $order->getGrandtotal();
        if ($orderTotal > 0) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getTotalOrderCount()
    {
        $order =  $this->getOrder();
        $customerId = $order->getCustomerId();
        if (!$customerId) {
            return 1;
        }

        $orderCollection = $this->orderCollectionFactory->create($customerId);
        return $orderCollection->count();
    }

    /**
     * @return double
     */
    public function getTotalLifetimeValue()
    {
        $order =  $this->getOrder();
        $customerId = $order->getCustomerId();

        if (!$customerId) {
            return $order->getGrandtotal();
        }

        $orderTotals = $this->orderCollectionFactory->create($customerId)
            ->addFieldToSelect('*');

        $grandTotals = $orderTotals->getColumnValues('grand_total');
        $refundTotals = $orderTotals->getColumnValues('total_refunded');

        return array_sum($grandTotals) - array_sum($refundTotals);
    }

    /**
     * @param string $countryCode
     * @return string
     */
    public function getCountryNameByCode($countryCode)
    {
        try {
            $country = $this->countryFactory->create()->loadByCode($countryCode);
        } catch (\Exception $e) {
            return $countryCode;
        }

        return $country->getName() ?? '';
    }

    /**
     * @return float|int
     */
    public function getOrderDiscountedAmount($useAlsoSpecialPriceAsDiscount = false)
    {
        if ($useAlsoSpecialPriceAsDiscount) {
            return $this->discountedAmount;
        }
        $order = $this->getOrder();
        return $order->getDiscountAmount();
    }

}
