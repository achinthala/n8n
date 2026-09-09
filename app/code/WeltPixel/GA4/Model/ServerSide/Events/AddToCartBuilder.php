<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\AddToCartInterface;
use WeltPixel\GA4\Api\ServerSide\Events\AddToCartInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\AddToCartItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class AddToCartBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface
{
    /**
     * @var AddToCartInterfaceFactory
     */
    protected $addToCartFactory;

    /**
     * @var AddToCartItemInterfaceFactory
     */
    protected $addToCartItemFactory;

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
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @param AddToCartInterfaceFactory $addToCartFactory
     * @param AddToCartItemInterfaceFactory $addToCartItemFactory
     * @param GA4Helper $ga4Helper
     * @param DimensionModel $dimensionModel
     * @param CustomerSession $customerSession
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     */
    public function __construct(
        AddToCartInterfaceFactory $addToCartFactory,
        AddToCartItemInterfaceFactory $addToCartItemFactory,
        GA4Helper $ga4Helper,
        DimensionModel $dimensionModel,
        CustomerSession $customerSession,
        \Magento\Framework\DataObject\Factory $objectFactory
    )
    {
        $this->addToCartFactory = $addToCartFactory;
        $this->addToCartItemFactory = $addToCartItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->dimensionModel = $dimensionModel;
        $this->customerSession = $customerSession;
        $this->objectFactory = $objectFactory;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param double $quantity
     * @param array $buyRequest
     * @param boolean $checkForCustomOptions
     * @return null|AddToCartInterface
     */
    function getAddToCartEvent($product, $quantity,  $buyRequest = [], $checkForCustomOptions = false)
    {
        /** @var AddToCartInterface $addToCartEvent */
        $addToCartEvent = $this->addToCartFactory->create();

        if (!$product) {
            return $addToCartEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $addToCartEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();
        $userId = $this->customerSession->getCustomerId();
        $currencyCode = $this->ga4Helper->getCurrencyCode();
        $productPrice = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));


        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $addToCartEvent->setUserId($userId);
        }
        $addToCartEvent->setPageLocation($pageLocation);
        $addToCartEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $addToCartEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $addToCartEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        $addToCartEvent->setCurrency($currencyCode);
        $addToCartEvent->setValue($productPrice * abs($quantity));

        $displayOption = $this->ga4Helper->getParentOrChildIdUsage();
        $productId = $this->ga4Helper->getGtmProductId($product);
        $itemName = html_entity_decode($product->getName() ?? '');

        if ($buyRequest instanceof \Magento\Framework\DataObject) {
            $buyRequest = $buyRequest->getData();
        }

        if ( ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) && ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)) {
            $canditatesRequest = $this->objectFactory->create($buyRequest);
            $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($canditatesRequest, $product);

            if (is_array($cartCandidates) || is_object($cartCandidates)) {
                foreach ($cartCandidates as $candidate) {
                    if ($candidate->getParentProductId()) {
                        $productId = $this->ga4Helper->getGtmProductId($candidate);
                        $itemName = html_entity_decode($candidate->getName() ?? '');
                    }
                }
            }
        }

        $addToCartItemOptions = [];
        $addToCartItemOptions['item_name'] = $itemName;
        $addToCartItemOptions['item_id'] = $productId;
        $addToCartItemOptions['affiliation'] = $this->ga4Helper->getAffiliationName();
        $addToCartItemOptions['price'] = $productPrice;
        if ($this->ga4Helper->isBrandEnabled()) {
            $addToCartItemOptions['item_brand'] = $this->ga4Helper->getGtmBrand($product);
        }

        $productCategoryIds = $product->getCategoryIds();
        $categoryName = $this->ga4Helper->getGtmCategoryFromCategoryIds($product->getCategoryIds());
        $ga4Categories = $this->ga4Helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
        $addToCartItemOptions = array_merge($addToCartItemOptions, $ga4Categories);
        $addToCartItemOptions['item_list_name'] = $categoryName;
        $addToCartItemOptions['item_list_id'] = count($productCategoryIds) ? $productCategoryIds[0] : '';
        $addToCartItemOptions['quantity'] = $quantity;

        /**  Set the custom dimensions */
        $customDimensions = $this->dimensionModel->getProductDimensions($product, $this->ga4Helper);
        foreach ($customDimensions as $name => $value) :
            $addToCartItemOptions[$name] = $value;
        endforeach;

        if ($this->ga4Helper->isVariantEnabled()) {
            $variant = $this->ga4Helper->checkVariantForProduct($product, $buyRequest, null, $checkForCustomOptions);
            if ($variant) {
                $addToCartItemOptions['item_variant'] = $variant;
            }
        }

        $addToCartItem = $this->addToCartItemFactory->create();
        $addToCartItem->setParams($addToCartItemOptions);

        $addToCartEvent->addItem($addToCartItem);

        return $addToCartEvent;
    }

}
