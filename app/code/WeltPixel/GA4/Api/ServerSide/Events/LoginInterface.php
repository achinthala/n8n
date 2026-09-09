<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface LoginInterface
{
    /**
     * @param $pageLocation
     * @return LoginInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return LoginInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return LoginInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return LoginInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return LoginInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return LoginInterface
     */
    function setUserId($userId);

    /**
     * @param $method
     * @return LoginInterface
     */
    function setMethod($method);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
