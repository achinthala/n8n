<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartInterface;
use WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartItemInterface;

class RemoveFromCart implements RemoveFromCartInterface
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
    protected $removeFromCartItems;

    /**
     * @var array
     */
    protected $removeFromCartEvent;

    public function __construct()
    {
        $this->removeFromCartEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->removeFromCartEvent['name'] = 'remove_from_cart';
        $this->eventParams = [];
        $this->removeFromCartItems = [];
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
        $this->eventParams['items'] = $this->removeFromCartItems;
        $this->removeFromCartEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->removeFromCartEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return RemoveFromCartInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return RemoveFromCartInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return RemoveFromCartInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return RemoveFromCartInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return RemoveFromCartInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return RemoveFromCartInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $currency
     * @return RemoveFromCartInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $value
     * @return RemoveFromCartInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }

    /**
     * @param RemoveFromCartItemInterface $removeFromCartItem
     * @return RemoveFromCartInterface
     */
    function addItem($removeFromCartItem)
    {
        $this->removeFromCartItems[] = $removeFromCartItem->getParams();
        return $this;
    }
}
