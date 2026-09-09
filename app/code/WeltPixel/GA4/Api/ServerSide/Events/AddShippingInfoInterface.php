<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddShippingInfoInterface
{
    /**
     * @param $pageLocation
     * @return AddShippingInfoInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return AddShippingInfoInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return AddShippingInfoInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return AddShippingInfoInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return AddShippingInfoInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return AddShippingInfoInterface
     */
    function setUserId($userId);

    /**
     * @param $currency
     * @return AddShippingInfoInterface
     */
    function setCurrency($currency);

    /**
     * @param $value
     * @return AddShippingInfoInterface
     */
    function setValue($value);

    /**
     * @param $coupon
     * @return AddShippingInfoInterface
     */
    function setCoupon($coupon);

    /**
     * @param $shippingTier
     * @return AddShippingInfoInterface
     */
    function setShippingTier($shippingTier);

    /**
     * @param AddShippingInfoItemInterface $addShippingInfoItem
     * @return AddShippingInfoInterface
     */
    function addItem($addShippingInfoItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
