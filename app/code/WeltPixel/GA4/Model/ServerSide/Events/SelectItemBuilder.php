<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\SelectItemInterface;
use WeltPixel\GA4\Api\ServerSide\Events\SelectItemInterfaceFactory;
use WeltPixel\GA4\Api\ServerSide\Events\SelectItemItemInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use WeltPixel\GA4\Model\Dimension as DimensionModel;

class SelectItemBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\SelectItemBuilderInterface
{
    /**
     * @var SelectItemInterfaceFactory
     */
    protected $selectItemFactory;

    /**
     * @var SelectItemItemInterfaceFactory
     */
    protected $selectItemItemFactory;

    /**
     * @var GA4Helper
     */
    protected $ga4Helper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var DimensionModel
     */
    protected $dimensionModel;

    /**
     * @param SelectItemInterfaceFactory $selectItemFactory
     * @param SelectItemItemInterfaceFactory $selectItemItemFactory
     * @param GA4Helper $ga4Helper
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerSession $customerSession
     * @param DimensionModel $dimensionModel
     */
    public function __construct(
        SelectItemInterfaceFactory $selectItemFactory,
        SelectItemItemInterfaceFactory $selectItemItemFactory,
        GA4Helper $ga4Helper,
        ProductRepositoryInterface $productRepository,
        CustomerSession $customerSession,
        DimensionModel $dimensionModel
    )
    {
        $this->selectItemFactory = $selectItemFactory;
        $this->selectItemItemFactory = $selectItemItemFactory;
        $this->ga4Helper = $ga4Helper;
        $this->productRepository = $productRepository;
        $this->customerSession = $customerSession;
        $this->dimensionModel = $dimensionModel;
    }

    /**
     * @param $productId
     * @param $listId
     * @param $listName
     * @param $index
     * @return null|SelectItemInterface
     */
    function getSelectItemEvent($productId, $listId, $listName, $index)
    {
        /** @var SelectItemInterface $selectItemEvent */
        $selectItemEvent = $this->selectItemFactory->create();

        if (!$productId || !$listId || !$listName) {
            return $selectItemEvent;
        }
        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Exception $ex) {
            return $selectItemEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $selectItemEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();
        $userId = $this->customerSession->getCustomerId();
        $currencyCode = $this->ga4Helper->getCurrencyCode();

        $productPrice = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));

        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $selectItemEvent->setUserId($userId);
        }
        $selectItemEvent->setPageLocation($pageLocation);
        $selectItemEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $selectItemEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $selectItemEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        $selectItemEvent->setItemListId($listId);
        $selectItemEvent->setItemListName($listName);

        $productItemOptions = [];
        $productItemOptions['item_name'] = html_entity_decode($product->getName() ?? '');
        $productItemOptions['item_id'] = $this->ga4Helper->getGtmProductId($product);
        $productItemOptions['affiliation'] = $this->ga4Helper->getAffiliationName();
        $productItemOptions['price'] = $productPrice;
        if ($this->ga4Helper->isBrandEnabled()) {
            $productItemOptions['item_brand'] = $this->ga4Helper->getGtmBrand($product);
        }

        $productCategoryIds = $product->getCategoryIds();
        $ga4Categories = $this->ga4Helper->getGA4CategoriesFromCategoryIds($productCategoryIds);
        $productItemOptions = array_merge($productItemOptions, $ga4Categories);
        $productItemOptions['quantity'] = 1;
        $productItemOptions['index'] = $index;
        $productItemOptions['item_list_id'] = $listId;
        $productItemOptions['item_list_name'] = $listName;

        /**  Set the custom dimensions */
        $customDimensions = $this->dimensionModel->getProductDimensions($product, $this->ga4Helper);
        foreach ($customDimensions as $name => $value) :
            $productItemOptions[$name] = $value;
        endforeach;

        $selectItemItem = $this->selectItemItemFactory->create();
        $selectItemItem->setParams($productItemOptions);

        $selectItemEvent->addItem($selectItemItem);

        return $selectItemEvent;
    }
}
