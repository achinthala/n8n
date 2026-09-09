<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface ViewItemListInterface
{
    /**
     * @param $pageLocation
     * @return ViewItemListInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return ViewItemListInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return ViewItemListInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return ViewItemListInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return ViewItemListInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return ViewItemListInterface
     */
    function setUserId($userId);

    /**
     * @param $listId
     * @return ViewItemListInterface
     */
    function setItemListId($listId);

    /**
     * @param $listName
     * @return ViewItemListInterface
     */
    function setItemListName($listName);

    /**
     * @param ViewItemListItemInterface $viewItemListItem
     * @return ViewItemListInterface
     */
    function addItem($viewItemListItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
