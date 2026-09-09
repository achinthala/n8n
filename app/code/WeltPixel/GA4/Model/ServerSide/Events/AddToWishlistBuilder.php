<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistInterface;
use WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class AddToWishlistBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistBuilderInterface
{
    /**
     * @var AddToWishlistInterfaceFactory
     */
    protected $addToWishlistFactory;

    /**
     * @var AddToWishlistItemInterfaceFactory
     */
    protected $addToWishlistItemFactory;

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
     * @param AddToWishlistInterfaceFactory $addToWishlistFactory
     * @param AddToWishlistItemInterfaceFactory $addToWishlistItemFactory
     * @param GA4Helper $ga4Helper
     * @param DimensionModel $dimensionModel
     * @param CustomerSession $customerSession
     */
    public function __construct(
        AddToWishlistInterfaceFactory $addToWishlistFactory,
        AddToWishlistItemInterfaceFactory $addToWishlistItemFactory,
        GA4Helper $ga4Helper,
        DimensionModel $dimensionModel,
        CustomerSession $customerSession
    )
    {
        $this->addToWishlistFactory = $addToWishlistFactory;
        $this->addToWishlistItemFactory = $addToWishlistItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->dimensionModel = $dimensionModel;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $buyRequest
     * @param \Magento\Wishlist\Model\Item $wishlistItem
     * @return null|AddToWishlistInterface
     */
    function getAddToWishlistEvent($product, $buyRequest, $wishlistItem)
    {
        /** @var AddToWishlistInterface $addToWishlistEvent */
        $addToWishlistEvent = $this->addToWishlistFactory->create();

        if (!$product) {
            return $addToWishlistEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $addToWishlistEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();
        $userId = $this->customerSession->getCustomerId();
        $currencyCode = $this->ga4Helper->getCurrencyCode();
        $productPrice = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));

        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $addToWishlistEvent->setUserId($userId);
        }
        $addToWishlistEvent->setPageLocation($pageLocation);
        $addToWishlistEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $addToWishlistEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $addToWishlistEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        $addToWishlistEvent->setCurrency($currencyCode);
        $addToWishlistEvent->setValue($productPrice);

        $addToWishlistItemOptions = [];
        $addToWishlistItemOptions['item_name'] = html_entity_decode($product->getName() ?? '');
        $addToWishlistItemOptions['item_id'] = $this->ga4Helper->getGtmProductId($product);
        $addToWishlistItemOptions['affiliation'] = $this->ga4Helper->getAffiliationName();
        $addToWishlistItemOptions['index'] = 0;
        $addToWishlistItemOptions['price'] = $productPrice;
        if ($this->ga4Helper->isBrandEnabled()) {
            $addToWishlistItemOptions['item_brand'] = $this->ga4Helper->getGtmBrand($product);
        }

        $productCategoryIds = $product->getCategoryIds();
        $categoryName = $this->ga4Helper->getGtmCategoryFromCategoryIds($product->getCategoryIds());
        $ga4Categories = $this->ga4Helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
        $addToWishlistItemOptions = array_merge($addToWishlistItemOptions, $ga4Categories);
        $addToWishlistItemOptions['item_list_name'] = $categoryName;
        $addToWishlistItemOptions['item_list_id'] = count($productCategoryIds) ? $productCategoryIds[0] : '';
        $addToWishlistItemOptions['quantity'] = 1;

        /**  Set the custom dimensions */
        $customDimensions = $this->dimensionModel->getProductDimensions($product, $this->ga4Helper);
        foreach ($customDimensions as $name => $value) :
            $addToWishlistItemOptions[$name] = $value;
        endforeach;

        if ($this->ga4Helper->isVariantEnabled()) {
            $variant = $this->ga4Helper->checkVariantForProduct($product, $buyRequest, $wishlistItem);
            if ($variant) {
                $addToWishlistItemOptions['item_variant'] = $variant;
            }
        }

        $addToWishlistItem = $this->addToWishlistItemFactory->create();
        $addToWishlistItem->setParams($addToWishlistItemOptions);

        $addToWishlistEvent->addItem($addToWishlistItem);

        return $addToWishlistEvent;
    }

}
