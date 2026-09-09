<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface SearchInterface
{
    /**
     * @param $pageLocation
     * @return SearchInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return SearchInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return SearchInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return SearchInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return SearchInterface
     */
    function setTimestamp($timestamp);


    /**
     * @param $userId
     * @return SearchInterface
     */
    function setUserId($userId);

    /**
     * @param $searchTerm
     * @return SearchInterface
     */
    function setSearchTerm($searchTerm);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
