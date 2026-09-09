<?php

namespace WeltPixel\GA4\Api\ServerSide;

interface ApiInterface
{

    /**
     * @param array $params
     * @return string
     */
    function getApiEndpoint($params = []);

    /**
     * @return string
     */
    function getMeasurementId();

    /**
     * @param $measurementId
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface
     */
    function setMeasurementId($measurementId);

    /**
     * @return string
     */
    function getApiSecret();

    /**
     * @param $apiSecret
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface
     */
    function setApiSecret($apiSecret);

    /**
     * @param Events\PurchaseInterface $purchaseEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    function pushPurchaseEvent(\WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterface $purchaseEvent);


    /**
     * @param Events\RefundInterface $refundEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    function pushRefundEvent(\WeltPixel\GA4\Api\ServerSide\Events\RefundInterface $refundEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SignupInterface $signupEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushSignupEvent(\WeltPixel\GA4\Api\ServerSide\Events\SignupInterface $signupEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\LoginInterface $loginEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushLoginEvent(\WeltPixel\GA4\Api\ServerSide\Events\LoginInterface $loginEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemInterface $viewItemEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushViewItemEvent(\WeltPixel\GA4\Api\ServerSide\Events\ViewItemInterface $viewItemEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemListInterface $viewItemListEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushViewItemListEvent(\WeltPixel\GA4\Api\ServerSide\Events\ViewItemListInterface $viewItemListEvent);


    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SelectItemInterface $selectItemEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushSelectItemEvent(\WeltPixel\GA4\Api\ServerSide\Events\SelectItemInterface $selectItemEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SearchInterface $searchEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushSearchEvent(\WeltPixel\GA4\Api\ServerSide\Events\SearchInterface $searchEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToCartInterface $addToCartEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushAddToCartEvent(\WeltPixel\GA4\Api\ServerSide\Events\AddToCartInterface $addToCartEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartInterface $removeFromCartEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushRemoveFromCartEvent(\WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartInterface $removeFromCartEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewCartInterface $viewCartEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushViewCartEvent(\WeltPixel\GA4\Api\ServerSide\Events\ViewCartInterface $viewCartEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutInterface $beginCheckoutEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushBeginCheckoutEvent(\WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutInterface $beginCheckoutEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddPaymentInfoInterface $addPaymentInfoEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushAddPaymentInfoEvent($addPaymentInfoEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoInterface $addShippingInfoEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushAddShippingInfoEvent($addShippingInfoEvent);

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistInterface $addToWishlistEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushAddToWishlistEvent($addToWishlistEvent);

}
