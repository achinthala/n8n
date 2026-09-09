<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoInterface;
use WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoItemInterface;

class AddShippingInfo implements AddShippingInfoInterface
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
    protected $addShippingInfoItems;

    /**
     * @var array
     */
    protected $addShippingInfoEvent;

    public function __construct()
    {
        $this->addShippingInfoEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->addShippingInfoEvent['name'] = 'add_shipping_info';
        $this->eventParams = [];
        $this->addShippingInfoItems = [];
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
        $this->eventParams['items'] = $this->addShippingInfoItems;
        $this->addShippingInfoEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->addShippingInfoEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return AddShippingInfoInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return AddShippingInfoInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return AddShippingInfoInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return AddShippingInfoInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return AddShippingInfoInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }


    /**
     * @param $userId
     * @return AddShippingInfoInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $currency
     * @return AddShippingInfoInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $value
     * @return AddShippingInfoInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }

    /**
     * @param $coupon
     * @return AddShippingInfoInterface
     */
    public function setCoupon($coupon)
    {
        $this->eventParams['coupon'] = $coupon;
        return $this;
    }

    /**
     * @param $shippingTier
     * @return AddShippingInfoInterface
     */
    public function setShippingTier($shippingTier)
    {
        $this->eventParams['shipping_tier'] = $shippingTier;
        return $this;
    }

    /**
     * @param  AddShippingInfoItemInterface $addShippingInfoItem
     * @return AddShippingInfoInterface
     */
    function addItem($addShippingInfoItem)
    {
        $this->addShippingInfoItems[] = $addShippingInfoItem->getParams();
        return $this;
    }
}
