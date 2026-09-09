<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\AddToCartInterface;
use WeltPixel\GA4\Api\ServerSide\Events\AddToCartItemInterface;

class AddToCart implements AddToCartInterface
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
    protected $addToCartItems;

    /**
     * @var array
     */
    protected $addToCartEvent;

    public function __construct()
    {
        $this->addToCartEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->addToCartEvent['name'] = 'add_to_cart';
        $this->eventParams = [];
        $this->addToCartItems = [];
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
        $this->eventParams['items'] = $this->addToCartItems;
        $this->addToCartEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->addToCartEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return AddToCartInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return AddToCartInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return AddToCartInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return AddToCartInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return AddToCartInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }


    /**
     * @param $userId
     * @return AddToCartInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $currency
     * @return AddToCartInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $value
     * @return AddToCartInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }

    /**
     * @param AddToCartItemInterface $addToCartItem
     * @return AddToCartInterface
     */
    function addItem($addToCartItem)
    {
        $this->addToCartItems[] = $addToCartItem->getParams();
        return $this;
    }
}
