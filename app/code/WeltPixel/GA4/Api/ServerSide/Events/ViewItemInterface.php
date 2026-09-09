<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewItemInterface
{
    /**
     * @param $pageLocation
     * @return ViewItemInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return ViewItemInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return ViewItemInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return ViewItemInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return ViewItemInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return ViewItemInterface
     */
    function setUserId($userId);

    /**
     * @param $currency
     * @return ViewItemInterface
     */
    function setCurrency($currency);

    /**
     * @param $value
     * @return ViewItemInterface
     */
    function setValue($value);

    /**
     * @param ViewItemItemInterface $viewItemItem
     * @return ViewItemInterface
     */
    function addItem($viewItemItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
