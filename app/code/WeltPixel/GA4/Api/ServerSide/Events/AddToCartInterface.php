<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddToCartInterface
{
    /**
     * @param $pageLocation
     * @return AddToCartInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return AddToCartInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return AddToCartInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return AddToCartInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return AddToCartInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return AddToCartInterface
     */
    function setUserId($userId);

    /**
     * @param $currency
     * @return AddToCartInterface
     */
    function setCurrency($currency);

    /**
     * @param $value
     * @return AddToCartInterface
     */
    function setValue($value);

    /**
     * @param AddToCartItemInterface $addToCartItem
     * @return AddToCartInterface
     */
    function addItem($addToCartItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
