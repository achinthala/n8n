<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Dcw\ShoppingCart\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Reorder\OrderInfoBuyRequestGetter;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Sales\Model\Order\Item;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Dcw\FlooringCalculation\ViewModel\Data as FlooringCalculationViewModel;

/**
 * Class ReorderPluginOverride.
 *
 * Override of NegotiableQuote ReorderPlugin with custom addItemToCart logic.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReorderPluginOverride
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    private $resultFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Reorder
     */
    private $reorder;

    /**
     * ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var OrderInfoBuyRequestGetter
     */
    private $orderInfoBuyRequestGetter;

    /**
     * @var FlooringCalculationViewModel
     */
    private $flooringCalculationViewModel;

    /**
     * Reorder constructor.
     *
     * @param Cart $cart
     * @param OrderLoaderInterface $orderLoader
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param Reorder|null $reorder
     * @param ProductCollectionFactory|null $productCollectionFactory
     * @param OrderInfoBuyRequestGetter|null $orderInfoBuyRequestGetter
     * @param FlooringCalculationViewModel|null $flooringCalculationViewModel
     */
    public function __construct(
        Cart $cart,
        OrderLoaderInterface $orderLoader,
        Context $context,
        OrderRepositoryInterface $orderRepository,
        AddressRepositoryInterface $addressRepository,
        \Psr\Log\LoggerInterface $logger,
        Reorder $reorder = null,
        ProductCollectionFactory $productCollectionFactory = null,
        OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter = null,
        FlooringCalculationViewModel $flooringCalculationViewModel = null
    ) {
        $this->cart = $cart;
        $this->orderLoader = $orderLoader;
        $this->messageManager = $context->getMessageManager();
        $this->resultFactory = $context->getResultFactory();
        $this->orderRepository = $orderRepository;
        $this->addressRepository = $addressRepository;
        $this->logger = $logger;
        $this->reorder = $reorder ?: ObjectManager::getInstance()->get(Reorder::class);
        $this->productCollectionFactory = $productCollectionFactory ?:
            ObjectManager::getInstance()->get(ProductCollectionFactory::class);
        $this->orderInfoBuyRequestGetter = $orderInfoBuyRequestGetter ?:
            ObjectManager::getInstance()->get(OrderInfoBuyRequestGetter::class);
        $this->flooringCalculationViewModel = $flooringCalculationViewModel ?:
            ObjectManager::getInstance()->get(FlooringCalculationViewModel::class);
    }

    /**
     * Reorder process for b2b.
     *
     * @param \Magento\Sales\Controller\Order\Reorder $subject
     * @param \Closure $proceed
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Exception
     */
    public function aroundExecute(
        \Magento\Sales\Controller\Order\Reorder $subject,
        \Closure $proceed
    ) {
        $orderId = $subject->getRequest()->getParam('order_id');
        $replaceCart = $subject->getRequest()->getParam('replace_cart');

        if (!$this->reorder->canReorder($orderId)) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $result->forward('noroute');
        }

        if ($orderId !== null && $replaceCart !== null) {
            $this->cart->truncate();
        }
        $result = $this->orderLoader->load($subject->getRequest());

        if ($result instanceof ResultInterface) {
            return $result;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        $this->addItemsToCart($this->cart, $order->getItemsCollection(), (string)$order->getStoreId());
        $this->cart->save();

        if ($order->getShippingAddress()) {
            $this->cart->getQuote()->getShippingAddress()->setShippingMethod($order->getShippingMethod());

            $this->orderAddressToQuoteAddress(
                $this->cart->getQuote()->getShippingAddress(),
                $order->getShippingAddress()
            );
        }

        $this->orderAddressToQuoteAddress(
            $this->cart->getQuote()->getBillingAddress(),
            $order->getBillingAddress()
        );

        $this->cart->getQuote()->getPayment()->setMethod($order->getPayment()->getMethod())->save();

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('checkout/cart');
    }

    /**
     * Adds order items to cart.
     *
     * @param Cart $cart
     * @param ItemCollection $orderItems
     * @param string $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    private function addItemsToCart(Cart $cart, ItemCollection $orderItems, string $storeId): void
    {
        $orderItemProductIds = [];
        /** @var \Magento\Sales\Model\Order\Item[] $orderItemsByProductId */
        $orderItemsByProductId = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($orderItems->getItems() as $item) {
            if ($item->getParentItem() === null) {
                $orderItemProductIds[] = $item->getProductId();
                $orderItemsByProductId[$item->getProductId()][$item->getId()] = $item;
            }
        }

        $products = $this->getOrderProducts($storeId, $orderItemProductIds);

        // compare founded sku and throw an error if some sku not exists
        $productsNotFound = array_diff($orderItemProductIds, array_keys($products));
        if (!empty($productsNotFound)) {
            foreach ($productsNotFound as $productId) {
                /** @var \Magento\Sales\Model\Order\Item $orderItemProductNotFound */
                $orderItemProductNotFound = $orderItemsByProductId[$productId];
                $this->addSkuNotFoundError(current($orderItemProductNotFound));
            }
        }

        foreach ($orderItemsByProductId as $productId => $orderItems) {
            if (!isset($products[$productId])) {
                continue;
            }
            $product = $products[$productId];
            foreach ($orderItems as $orderItem) {
                if ($product->isDisabled()) {
                    $this->addSkuNotFoundError($orderItem);
                    continue;
                }
                $this->addItemToCart($orderItem, $cart, clone $product);
            }
        }
    }

    /**
     * Get order products by store id and order item product ids.
     *
     * @param string $storeId
     * @param int[] $orderItemProductIds
     * @return Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrderProducts(string $storeId, array $orderItemProductIds): array
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($storeId)
            ->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner')
            ->addOptionsToResult();

        return $collection->getItems();
    }

    /**
     * Add sku not found error message
     *
     * @param Item $orderItem
     * @return void
     */
    private function addSkuNotFoundError(Item $orderItem): void
    {
        $this->messageManager->addError(__('Product with SKU %1 not found', $orderItem->getSku()));
    }

    /**
     * Adds order item product to cart with custom condition check.
     *
     * @param OrderItemInterface $orderItem
     * @param Cart $cart
     * @param ProductInterface $product
     */
    private function addItemToCart(OrderItemInterface $orderItem, Cart $cart, ProductInterface $product)
    {
        // CUSTOM CONDITION CHECK - Modify this based on your requirements
        $qty = $orderItem->getQtyOrdered();
        $sku = $orderItem->getSku();
        $loadProduct = $this->flooringCalculationViewModel->loadProductBySku($sku);
        
        $getPdpLineItem = $orderItem->getPdpLineItem();
        if ($getPdpLineItem && $loadProduct) {
            $data = json_decode($getPdpLineItem, true);
            $rollWidth = 0;
            if (isset($data['RoomWidth'])) {
                $rollWidth = (float)$data['RoomWidth'];
            }

            $rollLength = 0;
            if (isset($data['RoomLength'])) {
                $rollLength = (float)$data['RoomLength'];
            }

            if ($rollWidth > 0 && $rollLength > 0) {
                $orderLineItemQty = $rollLength * $qty;
                $getMiniCartQty = $this->flooringCalculationViewModel->getMiniCartQty($sku);
                $totalRequestedQty = $getMiniCartQty + $orderLineItemQty;
                $isProductCanAddToCart = $this->flooringCalculationViewModel->isProductCanAddToCart($loadProduct->getId(), $totalRequestedQty);
                $availableQty = $this->flooringCalculationViewModel->getProductAvailableQty($loadProduct->getId());
                
                if (!$isProductCanAddToCart) {
                    $this->messageManager->addErrorMessage(__('You can\'t order more than %1 linear feet. Requested: %2 linear feet.', $availableQty, $totalRequestedQty));
                    return;
                }
            }
        }

        $infoBuyRequest = $this->orderInfoBuyRequestGetter->getInfoBuyRequest($orderItem);

        try {
            $cart->addProduct($product, $infoBuyRequest);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager
                ->addErrorMessage(__('Product with SKU %1 not found in catalog', $orderItem->getSku()));
            $this->logger->critical($e);
        }
    }

    /**
     * Populates quote address based on order address info.
     *
     * @param AddressInterface $quoteAddress
     * @param OrderAddressInterface $orderAddress
     * @return void
     */
    private function orderAddressToQuoteAddress(AddressInterface $quoteAddress, OrderAddressInterface $orderAddress)
    {
        try {
            $addressData = $this->addressRepository->getById($orderAddress->getCustomerAddressId());
            $quoteAddress->importCustomerAddressData($addressData);
            $quoteAddress->save();
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (NoSuchEntityException $e) {
            // If no such entity, skip
        }
    }
}
