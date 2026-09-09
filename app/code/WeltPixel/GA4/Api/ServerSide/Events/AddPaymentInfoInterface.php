<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddPaymentInfoInterface
{
    /**
     * @param $pageLocation
     * @return AddPaymentInfoInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return AddPaymentInfoInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return AddPaymentInfoInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return AddPaymentInfoInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return AddPaymentInfoInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return AddPaymentInfoInterface
     */
    function setUserId($userId);

    /**
     * @param $currency
     * @return AddPaymentInfoInterface
     */
    function setCurrency($currency);

    /**
     * @param $value
     * @return AddPaymentInfoInterface
     */
    function setValue($value);

    /**
     * @param $coupon
     * @return AddPaymentInfoInterface
     */
    function setCoupon($coupon);

    /**
     * @param $paymentType
     * @return AddPaymentInfoInterface
     */
    function setPaymentType($paymentType);

    /**
     * @param AddPaymentInfoItemInterface $addPaymentInfoItem
     * @return AddPaymentInfoInterface
     */
    function addItem($addPaymentInfoItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
