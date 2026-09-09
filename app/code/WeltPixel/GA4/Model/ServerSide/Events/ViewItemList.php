<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\ViewItemListInterface;
use WeltPixel\GA4\Api\ServerSide\Events\ViewItemListItemInterface;

class ViewItemList implements ViewItemListInterface
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
    protected $viewItemListItems;

    /**
     * @var array
     */
    protected $viewItemListEvent;

    public function __construct()
    {
        $this->viewItemListEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->viewItemListEvent['name'] = 'view_item_list';
        $this->eventParams = [];
        $this->viewItemListItems = [];
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
        $this->eventParams['items'] = $this->viewItemListItems;
        $this->viewItemListEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->viewItemListEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return ViewItemListInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return ViewItemListInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return ViewItemListInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return ViewItemListInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return ViewItemListInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return ViewItemListInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $listId
     * @return ViewItemListInterface
     */
    function setItemListId($listId)
    {
        $this->eventParams['item_list_id'] = $listId;
        return $this;
    }

    /**
     * @param $listName
     * @return ViewItemListInterface
     */
    function setItemListName($listName)
    {
        $this->eventParams['item_list_name'] = $listName;
        return $this;
    }

    /**
     * @param ViewItemListItemInterface $viewItemListItem
     * @return ViewItemListInterface
     */
    function addItem($viewItemListItem)
    {
        $this->viewItemListItems[] = $viewItemListItem->getParams();
        return $this;
    }
}
