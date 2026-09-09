<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\SignupInterface;
use WeltPixel\GA4\Api\ServerSide\Events\SignupInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;

class SignupBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\SignupBuilderInterface
{
    /**
     * @var SignupInterfaceFactory
     */
    protected $signupFactory;

    /**
     * @var GA4Helper
     */
    protected $ga4Helper;

    /**
     * @param SignupInterfaceFactory $signupFactory
     * @param GA4Helper $ga4Helper
     */
    public function __construct(
        SignupInterfaceFactory $signupFactory,
        GA4Helper $ga4Helper
    )
    {
        $this->signupFactory = $signupFactory;
        $this->ga4Helper = $ga4Helper;
    }

    /**
     * @param int $customerId
     * @return null|SignupInterface
     */
    function getSignupEvent($customerId)
    {
        /** @var SignupInterface $signupEvent */
        $signupEvent = $this->signupFactory->create();

        if (!$customerId) {
            return $signupEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $signupEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();

        $signupEvent->setPageLocation($pageLocation);
        $signupEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $signupEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $signupEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        if ($this->ga4Helper->sendUserIdInEvents()) {
            $signupEvent->setUserId($customerId);
        }

        return $signupEvent;
    }

}
