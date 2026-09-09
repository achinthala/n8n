<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartInterface;
use WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class RemoveFromCartBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartBuilderInterface
{
    /**
     * @var RemoveFromCartInterfaceFactory
     */
    protected $removeFromCartFactory;

    /**
     * @var RemoveFromCartItemInterfaceFactory
     */
    protected $removeFromCartItemFactory;

    /**
     * @var GA4Helper
     */
    protected $ga4Helper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var DimensionModel
     */
    protected $dimensionModel;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @param RemoveFromCartInterfaceFactory $removeFromCartFactory
     * @param RemoveFromCartItemInterfaceFactory $removeFromCartItemFactory
     * @param GA4Helper $ga4Helper
     * @param CustomerSession $customerSession
     * @param DimensionModel $dimensionModel
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     */
    public function __construct(
        RemoveFromCartInterfaceFactory $removeFromCartFactory,
        RemoveFromCartItemInterfaceFactory $removeFromCartItemFactory,
        GA4Helper $ga4Helper,
        CustomerSession $customerSession,
        DimensionModel $dimensionModel,
        \Magento\Framework\DataObject\Factory $objectFactory
    )
    {
        $this->removeFromCartFactory = $removeFromCartFactory;
        $this->removeFromCartItemFactory = $removeFromCartItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->customerSession = $customerSession;
        $this->dimensionModel = $dimensionModel;
        $this->objectFactory = $objectFactory;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param double $quantity
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return null|RemoveFromCartInterface
     */
    public function getRemoveFromCartEvent($product, $quantity, $quoteItem)
    {
        /** @var RemoveFromCartInterface $removFromCartEvent */
        $removFromCartEvent = $this->removeFromCartFactory->create();

        if (!$product) {
            return $removFromCartEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $removFromCartEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();

        $userId = $this->customerSession->getCustomerId();
        $currencyCode = $this->ga4Helper->getCurrencyCode();
        $productPrice = floatval(number_format($this->ga4Helper->convertPriceToCurrentCurrency($quoteItem->getPrice()), 2, '.', ''));

        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $removFromCartEvent->setUserId($userId);
        }
        $removFromCartEvent->setPageLocation($pageLocation);
        $removFromCartEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $removFromCartEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $removFromCartEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        $removFromCartEvent->setCurrency($currencyCode);
        $removFromCartEvent->setValue($productPrice * abs($quantity));

        $productId = $this->ga4Helper->getGtmProductId($product);
        $itemName = html_entity_decode($product->getName() ?? '');

        $displayOption = $this->ga4Helper->getParentOrChildIdUsage();
        if ( ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) && ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)) {
            if ($quoteItem->getHasChildren()) {
                foreach ($quoteItem->getChildren() as $child) {
                    $childProduct = $child->getProduct();
                    $productId = $this->ga4Helper->getGtmProductId($childProduct);
                    $itemName = html_entity_decode($product->getName() ?? '');
                }
            }
        }

        $removeFromCartItemOptions = [];
        $removeFromCartItemOptions['item_name'] = $itemName;
        $removeFromCartItemOptions['item_id'] = $productId;
        $removeFromCartItemOptions['affiliation'] = $this->ga4Helper->getAffiliationName();
        $removeFromCartItemOptions['index'] = 0;
        $removeFromCartItemOptions['price'] = $productPrice;
        if ($this->ga4Helper->isBrandEnabled()) {
            $removeFromCartItemOptions['item_brand'] = $this->ga4Helper->getGtmBrand($product);
        }

        $productCategoryIds = $product->getCategoryIds();
        $categoryName = $this->ga4Helper->getGtmCategoryFromCategoryIds($product->getCategoryIds());
        $ga4Categories = $this->ga4Helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
        $removeFromCartItemOptions = array_merge($removeFromCartItemOptions, $ga4Categories);
        $removeFromCartItemOptions['item_list_name'] = $categoryName;
        $removeFromCartItemOptions['item_list_id'] = count($productCategoryIds) ? $productCategoryIds[0] : '';
        $removeFromCartItemOptions['quantity'] = $quantity;

        /**  Set the custom dimensions */
        $customDimensions = $this->dimensionModel->getProductDimensions($product, $this->ga4Helper);
        foreach ($customDimensions as $name => $value) :
            $removeFromCartItemOptions[$name] = $value;
        endforeach;

        if ($this->ga4Helper->isVariantEnabled()) {
            $productFromQuote = $quoteItem->getProduct();
            $variant = $this->ga4Helper->checkVariantForProduct($productFromQuote);
            if ($variant) {
                $removeFromCartItemOptions['item_variant'] = $variant;
            }
        }

        $removeFromCartItem = $this->removeFromCartItemFactory->create();
        $removeFromCartItem->setParams($removeFromCartItemOptions);

        $removFromCartEvent->addItem($removeFromCartItem);

        return $removFromCartEvent;
    }
}
