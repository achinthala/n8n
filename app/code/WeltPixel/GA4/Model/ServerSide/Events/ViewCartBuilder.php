<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\ViewCartInterface;
use WeltPixel\GA4\Api\ServerSide\Events\ViewCartInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\ViewCartItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class ViewCartBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\ViewCartBuilderInterface
{
    /**
     * @var ViewCartInterfaceFactory
     */
    protected $viewCartFactory;

    /**
     * @var ViewCartItemInterfaceFactory
     */
    protected $viewCartItemFactory;

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
     * @param ViewCartInterfaceFactory $viewCartFactory
     * @param ViewCartItemInterfaceFactory $viewCartItemFactory
     * @param GA4Helper $ga4Helper
     * @param DimensionModel $dimensionModel
     * @param CustomerSession $customerSession
     */
    public function __construct(
        ViewCartInterfaceFactory $viewCartFactory,
        ViewCartItemInterfaceFactory $viewCartItemFactory,
        GA4Helper $ga4Helper,
        DimensionModel $dimensionModel,
        CustomerSession $customerSession
    )
    {
        $this->viewCartFactory = $viewCartFactory;
        $this->viewCartItemFactory = $viewCartItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->dimensionModel = $dimensionModel;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return null|ViewCartInterface
     */
    function getViewCartEvent($quote)
    {
        /** @var ViewCartInterface $viewCartEvent */
        $viewCartEvent = $this->viewCartFactory->create();

        if (!$quote->getId()) {
            return $viewCartEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $viewCartEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation(false);
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();
        $userId = $this->customerSession->getCustomerId();
        $currencyCode = $this->ga4Helper->getCurrencyCode();

        $viewCartEvent->setPageLocation($pageLocation);
        $viewCartEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $viewCartEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $viewCartEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $viewCartEvent->setUserId($userId);
        }
        $viewCartEvent->setCurrency($currencyCode);
        $viewCartEvent->setValue(floatval(number_format($quote->getGrandTotal(), 2, '.', '')));

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

            $viewCartItem = $this->viewCartItemFactory->create();
            $viewCartItem->setParams($productItemOptions);

            $viewCartEvent->addItem($viewCartItem);
        }

        return $viewCartEvent;
    }

}
