<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface SignupInterface
{
    /**
     * @param $pageLocation
     * @return SignupInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return SignupInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return SignupInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return SignupInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return SignupInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return SignupInterface
     */
    function setUserId($userId);

    /**
     * @param $method
     * @return SignupInterface
     */
    function setMethod($method);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
