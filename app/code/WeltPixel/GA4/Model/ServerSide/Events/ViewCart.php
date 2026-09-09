<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\ViewCartInterface;
use WeltPixel\GA4\Api\ServerSide\Events\ViewCartItemInterface;

class ViewCart implements ViewCartInterface
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
    protected $viewCartItems;

    /**
     * @var array
     */
    protected $viewCartEvent;

    public function __construct()
    {
        $this->viewCartEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->viewCartEvent['name'] = 'view_cart';
        $this->eventParams = [];
        $this->viewCartItems = [];
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
        $this->eventParams['items'] = $this->viewCartItems;
        $this->viewCartEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->viewCartEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return ViewCartInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return ViewCartInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return ViewCartInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return ViewCartInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return ViewCartInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return ViewCartInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $currency
     * @return ViewCartInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $value
     * @return ViewCartInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }

    /**
     * @param ViewCartItemInterface $viewCartItem
     * @return ViewCartInterface
     */
    function addItem($viewCartItem)
    {
        $this->viewCartItems[] = $viewCartItem->getParams();
        return $this;
    }
}
