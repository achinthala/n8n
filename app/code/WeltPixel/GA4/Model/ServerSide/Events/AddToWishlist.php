<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistInterface;
use WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistItemInterface;

class AddToWishlist implements AddToWishlistInterface
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
    protected $addToWishlistItems;

    /**
     * @var array
     */
    protected $addToWishlistEvent;

    public function __construct()
    {
        $this->addToWishlistEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->addToWishlistEvent['name'] = 'add_to_wishlist';
        $this->eventParams = [];
        $this->addToWishlistItems = [];
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
        $this->eventParams['items'] = $this->addToWishlistItems;
        $this->addToWishlistEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->addToWishlistEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return AddToWishlistInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return AddToWishlistInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return AddToWishlistInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return AddToWishlistInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return AddToWishlistInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return AddToWishlistInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $currency
     * @return AddToWishlistInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $value
     * @return AddToWishlistInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }

    /**
     * @param AddToWishlistItemInterface $addToWishlistItem
     * @return AddToWishlistInterface
     */
    function addItem($addToWishlistItem)
    {
        $this->addToWishlistItems[] = $addToWishlistItem->getParams();
        return $this;
    }
}
