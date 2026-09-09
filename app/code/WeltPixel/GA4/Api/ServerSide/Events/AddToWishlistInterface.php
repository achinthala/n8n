<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddToWishlistInterface
{
    /**
     * @param $pageLocation
     * @return AddToWishlistInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return AddToWishlistInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return AddToWishlistInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return AddToWishlistInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return AddToWishlistInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return AddToWishlistInterface
     */
    function setUserId($userId);

    /**
     * @param $currency
     * @return AddToWishlistInterface
     */
    function setCurrency($currency);

    /**
     * @param $value
     * @return AddToWishlistInterface
     */
    function setValue($value);

    /**
     * @param AddToWishlistItemInterface $addToCartItem
     * @return AddToWishlistInterface
     */
    function addItem($addToCartItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
