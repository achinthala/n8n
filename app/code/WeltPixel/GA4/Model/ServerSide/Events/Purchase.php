<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterface;
use WeltPixel\GA4\Api\ServerSide\Events\PurchaseItemInterface;

class Purchase implements PurchaseInterface
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
    protected $purchaseItems;

    /**
     * @var array
     */
    protected $purchaseEvent;

    public function __construct()
    {
        $this->purchaseEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->purchaseEvent['name'] = 'purchase';
        $this->eventParams = [];
        $this->purchaseItems = [];
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
        $this->eventParams['items'] = $this->purchaseItems;
        $this->purchaseEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->purchaseEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return PurchaseInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return PurchaseInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return PurchaseInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return PurchaseInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return PurchaseInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return PurchaseInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }


    /**
     * @param $currency
     * @return PurchaseInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $transactionId
     * @return PurchaseInterface
     */
    public function setTransactionId($transactionId)
    {
        $this->eventParams['transaction_id'] = $transactionId;
        return $this;
    }

    /**
     * @param $value
     * @return PurchaseInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }

    /**
     * @param $coupon
     * @return PurchaseInterface
     */
    public function setCoupon($coupon)
    {
        $this->eventParams['coupon'] = $coupon;
        return $this;
    }

    /**
     * @param $shipping
     * @return PurchaseInterface
     */
    public function setShipping($shipping)
    {
        $this->eventParams['shipping'] = $shipping;
        return $this;
    }

    /**
     * @param $tax
     * @return PurchaseInterface
     */
    public function setTax($tax)
    {
        $this->eventParams['tax'] = $tax;
        return $this;
    }

    /**
     * @param PurchaseItemInterface $purchaseItem
     * @return PurchaseInterface
     */
    function addItem($purchaseItem)
    {
        $this->purchaseItems[] = $purchaseItem->getParams();
        return $this;
    }
}
