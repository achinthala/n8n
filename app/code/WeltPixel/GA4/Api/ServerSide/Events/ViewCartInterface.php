<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewCartInterface
{
    /**
     * @param $pageLocation
     * @return ViewCartInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return ViewCartInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return ViewCartInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return ViewCartInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return ViewCartInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return ViewCartInterface
     */
    function setUserId($userId);

    /**
     * @param $currency
     * @return ViewCartInterface
     */
    function setCurrency($currency);

    /**
     * @param $value
     * @return ViewCartInterface
     */
    function setValue($value);

    /**
     * @param ViewCartItemInterface $viewCartItem
     * @return ViewCartInterface
     */
    function addItem($viewCartItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
