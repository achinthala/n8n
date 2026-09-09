<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutInterface;
use WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class BeginCheckoutBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutBuilderInterface
{
    /**
     * @var BeginCheckoutInterfaceFactory
     */
    protected $beginCheckoutFactory;

    /**
     * @var BeginCheckoutItemInterfaceFactory
     */
    protected $beginCheckoutItemFactory;

    /**
     * @var GA4Helper
     */
    protected $ga4Helper;

    /**
     * @var DimensionModel
     */
    protected $dimensionModel;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param BeginCheckoutInterfaceFactory $beginCheckoutFactory
     * @param BeginCheckoutItemInterfaceFactory $beginCheckoutItemFactory
     * @param GA4Helper $ga4Helper
     * @param DimensionModel $dimensionModel
     * @param CustomerSession $customerSession
     */
    public function __construct(
        BeginCheckoutInterfaceFactory $beginCheckoutFactory,
        BeginCheckoutItemInterfaceFactory $beginCheckoutItemFactory,
        GA4Helper $ga4Helper,
        DimensionModel $dimensionModel,
        CustomerSession $customerSession
    )
    {
        $this->beginCheckoutFactory = $beginCheckoutFactory;
        $this->beginCheckoutItemFactory = $beginCheckoutItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->dimensionModel = $dimensionModel;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return null|BeginCheckoutInterface
     */
    function getBeginCheckoutEvent($quote)
    {
        /** @var BeginCheckoutInterface $beginCheckoutEvent */
        $beginCheckoutEvent = $this->beginCheckoutFactory->create();

        if (!$quote->getId()) {
            return $beginCheckoutEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $beginCheckoutEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation(false);
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();
        $userId = $this->customerSession->getCustomerId();
        $currencyCode = $this->ga4Helper->getCurrencyCode();

        $beginCheckoutEvent->setPageLocation($pageLocation);
        $beginCheckoutEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $beginCheckoutEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $beginCheckoutEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $beginCheckoutEvent->setUserId($userId);
        }
        $beginCheckoutEvent->setCurrency($currencyCode);
        $beginCheckoutEvent->setValue(floatval(number_format($quote->getGrandTotal(), 2, '.', '')));
        if ($quote->getCouponCode()) {
            $beginCheckoutEvent->setCoupon($quote->getCouponCode());
        }

        $displayOption = $this->ga4Helper->getParentOrChildIdUsage();

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIdModel = $product;
            if ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildren();
                    foreach ($children as $child) {
                        $productIdModel = $child->getProduct();
                    }
                }
            }

            $productItemOptions = [];
            $productItemOptions['item_name'] = html_entity_decode($productIdModel->getName() ?? '');
            $productItemOptions['item_id'] = $this->ga4Helper->getGtmProductId($productIdModel);
            $productItemOptions['affiliation'] = $this->ga4Helper->getAffiliationName();
            $productItemOptions['price'] = floatval(number_format($item->getPriceInclTax() ?? 0, 2, '.', ''));
            if ($this->ga4Helper->isBrandEnabled()) {
                $productItemOptions['item_brand'] = $this->ga4Helper->getGtmBrand($product);
            }
            if ($this->ga4Helper->isVariantEnabled()) {
                $variant = $this->ga4Helper->checkVariantForProduct($product);
                if ($variant) {
                    $productItemOptions['item_variant'] = $variant;
                }
            }
            $productCategoryIds = $product->getCategoryIds();
            $categoryName =  $this->ga4Helper->getGtmCategoryFromCategoryIds($productCategoryIds);
            $ga4Categories = $this->ga4Helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
            $productItemOptions = array_merge($productItemOptions, $ga4Categories);
            $productItemOptions['item_list_name'] = $categoryName;
            $productItemOptions['item_list_id'] = count($productCategoryIds) ? $productCategoryIds[0] : '';
            $productItemOptions['quantity'] = (double)$item->getQty();

            /**  Set the custom dimensions */
            $customDimensions = $this->dimensionModel->getProductDimensions($product,  $this->ga4Helper);
            foreach ($customDimensions as $name => $value) :
                $productItemOptions[$name] = $value;
            endforeach;

            $beginCheckoutItem = $this->beginCheckoutItemFactory->create();
            $beginCheckoutItem->setParams($productItemOptions);

            $beginCheckoutEvent->addItem($beginCheckoutItem);
        }

        return $beginCheckoutEvent;
    }

}
