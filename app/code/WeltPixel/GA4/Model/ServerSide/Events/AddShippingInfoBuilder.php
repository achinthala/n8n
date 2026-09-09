<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoInterface;
use WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class AddShippingInfoBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoBuilderInterface
{
    /**
     * @var AddShippingInfoInterfaceFactory
     */
    protected $addShippingInfoFactory;

    /**
     * @var AddShippingInfoItemInterfaceFactory
     */
    protected $addShippingInfoItemFactory;

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
     * @param AddShippingInfoInterfaceFactory $addShippingInfoFactory
     * @param AddShippingInfoItemInterfaceFactory $addPShippingInfoItemFactory
     * @param GA4Helper $ga4Helper
     * @param CustomerSession $customerSession
     * @param DimensionModel $dimensionModel
     */
    public function __construct(
        AddShippingInfoInterfaceFactory $addShippingInfoFactory,
        AddShippingInfoItemInterfaceFactory $addShippingInfoItemFactory,
        GA4Helper $ga4Helper,
        CustomerSession $customerSession,
        DimensionModel $dimensionModel
    )
    {
        $this->addShippingInfoFactory = $addShippingInfoFactory;
        $this->addShippingInfoItemFactory = $addShippingInfoItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->customerSession = $customerSession;
        $this->dimensionModel = $dimensionModel;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $shippingTier
     * @return null|AddShippingInfoInterface
     */
    function getAddShippingInfoEvent($quote, $shippingTier)
    {
        /** @var AddShippingInfoInterface $addShippingInfoEvent */
        $addShippingInfoEvent = $this->addShippingInfoFactory->create();

        if (!$quote) {
            return $addShippingInfoEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $addShippingInfoEvent->setUserProperties($userProperties);
        }
        $userId = $this->customerSession->getCustomerId();
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();

        $currencyCode = $this->ga4Helper->getCurrencyCode();

        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $addShippingInfoEvent->setUserId($userId);
        }
        $addShippingInfoEvent->setPageLocation($pageLocation);
        $addShippingInfoEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $addShippingInfoEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $addShippingInfoEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        if ($quote->getCouponCode()) {
            $addShippingInfoEvent->setCoupon((string)$quote->getCouponCode());
        }

        $addShippingInfoEvent->setValue(floatval(number_format($quote->getGrandTotal(), 2, '.', '')));
        $addShippingInfoEvent->setCurrency($currencyCode);
        $addShippingInfoEvent->setShippingTier($shippingTier);

        $displayOption = $this->ga4Helper->getParentOrChildIdUsage();

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIdModel = $product;
            if ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) {
                if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    $children = $item->getChildren();
                    if ($children) {
                        foreach ($children as $child) {
                            $productIdModel = $child->getProduct();
                        }
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
                $variant = $this->ga4Helper->checkVariantForProduct($product);
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
            $productItemOptions['quantity'] = (double)$item->getQty();

            /**  Set the custom dimensions */
            $customDimensions = $this->dimensionModel->getProductDimensions($product, $this->ga4Helper);
            foreach ($customDimensions as $name => $value) :
                $productItemOptions[$name] = $value;
            endforeach;

            $addShippingInfoItem = $this->addShippingInfoItemFactory->create();
            $addShippingInfoItem->setParams($productItemOptions);

            $addShippingInfoEvent->addItem($addShippingInfoItem);
        }

        return $addShippingInfoEvent;
    }

}
