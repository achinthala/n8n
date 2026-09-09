<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\SearchInterface;

class Search implements SearchInterface
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
    protected $searchEvent;

    public function __construct()
    {
        $this->searchEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->searchEvent['name'] = 'search';
        $this->eventParams = [];
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
        $this->searchEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->searchEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return SearchInterface
     */
    function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $clientId
     * @return SearchInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return SearchInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return SearchInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return SearchInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return SearchInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        return $this;
    }

    /**
     * @param $searchTerm
     * @return SearchInterface
     */
    public function setSearchTerm($searchTerm)
    {
        $this->eventParams['search_term'] = $searchTerm;
        return $this;
    }
}
