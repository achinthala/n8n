<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface RemoveFromCartInterface
{
    /**
     * @param $pageLocation
     * @return RemoveFromCartInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return RemoveFromCartInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return RemoveFromCartInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return RemoveFromCartInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return RemoveFromCartInterface
     */
    function setTimestamp($timestamp);


    /**
     * @param $userId
     * @return RemoveFromCartInterface
     */
    function setUserId($userId);

    /**
     * @param $currency
     * @return RemoveFromCartInterface
     */
    function setCurrency($currency);

    /**
     * @param $value
     * @return RemoveFromCartInterface
     */
    function setValue($value);

    /**
     * @param RemoveFromCartItemInterface $removeFromCartItem
     * @return RemoveFromCartInterface
     */
    function addItem($removeFromCartItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
