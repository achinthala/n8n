<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\MagentoDataServices\ViewModel;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;

class CheckoutSuccessContextProvider implements ArgumentInterface
{
    private Json $json;
    private CheckoutSession $checkoutSession;
    private ProductHelper $productHelper;
    private CheckoutHelper $checkoutHelper;

    public function __construct(
        CheckoutSession $checkoutSession,
        ProductHelper $productHelper,
        CheckoutHelper $checkoutHelper,
        Json $json
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productHelper = $productHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->json = $json;
    }

    public function getLastOrderCartContextData(): string
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $items = $this->getOrderItems($order);

        $context = [
            'id' => $this->checkoutSession->getLastRealOrder()->getQuoteId(),
            'totalQuantity' => count($items),
            'prices' => [
                'subtotalExcludingTax' => [
                    'value' => (float)$order->getBaseSubtotal()
                ],
                'subtotalIncludingTax' => [
                    'value' => (float)$order->getSubtotalInclTax()
                ]
            ],
            'possibleOnepageCheckout' => $this->checkoutHelper->canOnepageCheckout(),
            'giftMessageSelected' => (bool)$order->getGiftMessageId(),
            'giftWrappingSelected' => false,
            'items' => $items
        ];

        return $this->json->serialize($context);
    }

    public function getOrderId(): string
    {
        return $this->checkoutSession->getLastRealOrder()->getRealOrderId();
    }

    public function getCustomerEmail(): string
    {
        return (string)$this->checkoutSession->getLastRealOrder()->getCustomerEmail();
    }

    public function getPayment(): array
    {
        $order = $this->checkoutSession->getLastRealOrder();

        $payment = $order->getPayment();
        // Payment might not exist for async placed orders.
        if ($payment) {
            $paymentData['total'] = round((float)$payment->getAmountOrdered(), 2);
            $paymentData['paymentMethodCode'] = $payment->getMethod();
            $paymentData['paymentMethodName'] = $payment->getMethodInstance()->getTitle();
        }

        return [$paymentData];
    }

    public function getShipping(): array
    {
        return [
            'shippingMethod' => $this->checkoutSession->getLastRealOrder()->getShippingMethod(),
            'shippingAmount' => round((float)$this->checkoutSession->getLastRealOrder()->getShippingAmount(), 2),
        ];
    }

    public function getOrderContext(): string
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $couponCode = $order->getCouponCode();

        $orderContext = [
            'orderId' => (int)$order->getIncrementId(),
            'payments' => $this->getPayment(),
            'shipping' => $this->getShipping(),
            'discountAmount' => round((float)$order->getDiscountAmount(), 2),
            'grandTotal' => round((float)$order->getGrandTotal(), 2),
            'taxAmount' => round((float)$order->getTaxAmount(), 2),
        ];

        if ($couponCode) {
            $orderContext['appliedCouponCode'] = $couponCode;
        }

        return $this->json->serialize($orderContext);
    }

    private function getOrderItems(Order $order) : array
    {
        $context = [];
        $items = $order->getAllVisibleItems();

        foreach ($items as $item) {
            /** @var OrderItem $item */
            $context[] = [
                'id' => $item->getItemId(),
                'formattedPrice' => (float)$item->getPriceInclTax(),
                'quantity' => (int)$item->getQtyOrdered(),
                'prices' => [
                    'price' => [
                        'value' => (float)$item->getPriceInclTax()
                    ]
                ],
                'product' => $this->getItemProductData($item)
            ];
        }

        return $context;
    }

    private function priceAsFloat($price): ?float
    {
        return $price === null ? null : (float) $price;
    }

    private function getItemProductData(OrderItem $item) : array
    {
        $product = $item->getProduct();
        return [
            'productType' => $item->getProductType(),
            'productId' => $product->getId(),
            'name' => $item->getName(),
            'sku' => $item->getSku(),
            'mainImageUrl' => $this->productHelper->getImageUrl($product),
            'pricing' => [
                'regularPrice' => $this->priceAsFloat($product->getPrice()),
                'minimalPrice' => $this->priceAsFloat($product->getMinimalPrice()),
                'specialPrice' => $this->priceAsFloat($product->getSpecialPrice())
            ],
        ];
    }
}
