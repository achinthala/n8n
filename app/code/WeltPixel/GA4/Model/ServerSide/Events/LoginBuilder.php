<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\LoginInterface;
use WeltPixel\GA4\Api\ServerSide\Events\LoginInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;

class LoginBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\LoginBuilderInterface
{
    /**
     * @var LoginInterfaceFactory
     */
    protected $loginFactory;

    /**
     * @var GA4Helper
     */
    protected $ga4Helper;

    /**
     * @param LoginInterfaceFactory $loginFactory
     * @param GA4Helper $ga4Helper
     */
    public function __construct(
        LoginInterfaceFactory $loginFactory,
        GA4Helper $ga4Helper
    )
    {
        $this->loginFactory = $loginFactory;
        $this->ga4Helper = $ga4Helper;
    }

    /**
     * @param int $customerId
     * @return null|LoginInterface
     */
    function getLoginEvent($customerId)
    {
        /** @var LoginInterface $loginEvent */
        $loginEvent = $this->loginFactory->create();

        if (!$customerId) {
            return $loginEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $loginEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();

        $loginEvent->setPageLocation($pageLocation);
        $loginEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $loginEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $loginEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }

        if ($this->ga4Helper->sendUserIdInEvents()) {
            $loginEvent->setUserId($customerId);
        }

        return $loginEvent;
    }

}
