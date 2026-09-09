<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutInterface;
use WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutItemInterface;

class BeginCheckout implements BeginCheckoutInterface
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
    protected $beginCheckoutItems;

    /**
     * @var array
     */
    protected $beginCheckoutEvent;

    public function __construct()
    {
        $this->beginCheckoutEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->beginCheckoutEvent['name'] = 'begin_checkout';
        $this->eventParams = [];
        $this->beginCheckoutItems = [];
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
        $this->eventParams['items'] = $this->beginCheckoutItems;
        $this->beginCheckoutEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->beginCheckoutEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return BeginCheckoutInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return BeginCheckoutInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return BeginCheckoutInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return BeginCheckoutInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return BeginCheckoutInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return BeginCheckoutInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $currency
     * @return BeginCheckoutInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $value
     * @return BeginCheckoutInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }


    /**
     * @param $coupon
     * @return BeginCheckoutInterface
     */
    public function setCoupon($coupon)
    {
        $this->eventParams['coupon'] = $coupon;
        return $this;
    }

    /**
     * @param BeginCheckoutItemInterface $beginCheckoutItem
     * @return BeginCheckoutInterface
     */
    function addItem($beginCheckoutItem)
    {
        $this->beginCheckoutItems[] = $beginCheckoutItem->getParams();
        return $this;
    }
}
