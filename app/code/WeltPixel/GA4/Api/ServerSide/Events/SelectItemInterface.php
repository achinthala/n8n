<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

use WeltPixel\GA4\Model\ServerSide\Events\SelectItem;

interface SelectItemInterface
{
    /**
     * @param $pageLocation
     * @return SelectItemInterface
     */
    function setPageLocation($pageLocation);

    /**
     * @param $clientId
     * @return SelectItemInterface
     */
    function setClientId($clientId);

    /**
     * @param $userProperties
     * @return SelectItemInterface
     */
    function setUserProperties($userProperties);

    /**
     * @param $sessionId
     * @return SelectItemInterface
     */
    function setSessionId($sessionId);

    /**
     * @param $timestamp
     * @return SelectItemInterface
     */
    function setTimestamp($timestamp);

    /**
     * @param $userId
     * @return SelectItemInterface
     */
    function setUserId($userId);

    /**
     * @param $listId
     * @return SelectItemInterface
     */
    function setItemListId($listId);

    /**
     * @param $listName
     * @return SelectItemInterface
     */
    function setItemListName($listName);

    /**
     * @param SelectItemItemInterface $selectItemItem
     * @return SelectItemInterface
     */
    function addItem($selectItemItem);

    /**
     * @param bool $debugMode
     * @return array
     */
    function getParams($debugMode = false);
}
