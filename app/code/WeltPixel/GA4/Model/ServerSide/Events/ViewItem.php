<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\ViewItemInterface;
use WeltPixel\GA4\Api\ServerSide\Events\ViewItemItemInterface;

class ViewItem implements ViewItemInterface
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
    protected $viewItemItems;

    /**
     * @var array
     */
    protected $viewItemEvent;

    public function __construct()
    {
        $this->viewItemEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->viewItemEvent['name'] = 'view_item';
        $this->eventParams = [];
        $this->viewItemItems = [];
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
        $this->eventParams['items'] = $this->viewItemItems;
        $this->viewItemEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->viewItemEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return ViewItemInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return ViewItemInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return ViewItemInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return ViewItemInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return ViewItemInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return ViewItemInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $currency
     * @return ViewItemInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }


    /**
     * @param $value
     * @return ViewItemInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }


    /**
     * @param ViewItemItemInterface $viewItemItem
     * @return ViewItemInterface
     */
    function addItem($viewItemItem)
    {
        $this->viewItemItems[] = $viewItemItem->getParams();
        return $this;
    }
}
