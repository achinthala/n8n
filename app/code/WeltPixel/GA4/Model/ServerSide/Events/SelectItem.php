<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\SelectItemInterface;
use WeltPixel\GA4\Api\ServerSide\Events\SelectItemItemInterface;

class SelectItem implements SelectItemInterface
{
    /**
     * @var array
     */
    protected $payloadData;

    /**
     * @var array
     */
    protected $eventParams;

    /**
     * @var array
     */
    protected $selectItemItems;

    /**
     * @var array
     */
    protected $selectItemEvent;

    public function __construct()
    {
        $this->selectItemEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->selectItemEvent['name'] = 'select_item';
        $this->eventParams = [];
        $this->selectItemItems = [];
    }

    /**
     * @param bool $debugMode
     * @return array
     */
    public function getParams($debugMode = false)
    {
        if ($debugMode) {
            $this->eventParams['debug_mode'] = 1;
        }
        $this->eventParams['items'] = $this->selectItemItems;
        $this->selectItemEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->selectItemEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return SelectItemInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return SelectItemInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return SelectItemInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return SelectItemInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return SelectItemInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return SelectItemInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $listId
     * @return SelectItemInterface
     */
    public function setItemListId($listId)
    {
        $this->eventParams['item_list_id'] = $listId;
        return $this;
    }


    /**
     * @param $listName
     * @return SelectItemInterface
     */
    public function setItemListName($listName)
    {
        $this->eventParams['item_list_name'] = $listName;
        return $this;
    }

    /**
     * @param SelectItemItemInterface $selectItemItem
     * @return SelectItemInterface
     */
    function addItem($selectItemItem)
    {
        $this->selectItemItems[] = $selectItemItem->getParams();
        return $this;
    }
}
