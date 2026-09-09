<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use Magento\Customer\Model\Session as CustomerSession;
use WeltPixel\GA4\Api\ServerSide\Events\SearchInterface;
use WeltPixel\GA4\Api\ServerSide\Events\SearchInterfaceFactory;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;

class SearchBuilder implements \WeltPixel\GA4\Api\ServerSide\Events\SearchBuilderInterface
{
    /**
     * @var SearchInterfaceFactory
     */
    protected $searchFactory;

    /**
     * @var GA4Helper
     */
    protected $ga4Helper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param SearchInterfaceFactory $searchFactory
     * @param GA4Helper $ga4Helper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        SearchInterfaceFactory $searchFactory,
        GA4Helper $ga4Helper,
        CustomerSession $customerSession
    )
    {
        $this->searchFactory = $searchFactory;
        $this->ga4Helper = $ga4Helper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param int $searchTerm
     * @return null|SearchInterface
     */
    function getSearchEvent($searchTerm)
    {
        /** @var SearchInterface $searchEvent */
        $searchEvent = $this->searchFactory->create();

        if (!$searchTerm) {
            return $searchEvent;
        }

        $userProperties = $this->ga4Helper->getUserProperties();
        if ($userProperties) {
            $searchEvent->setUserProperties($userProperties);
        }
        $pageLocation = $this->ga4Helper->getPageLocation();
        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();
        $userId = $this->customerSession->getCustomerId();

        if ($this->ga4Helper->sendUserIdInEvents() && $userId) {
            $searchEvent->setUserId($userId);
        }
        $searchEvent->setPageLocation($pageLocation);
        $searchEvent->setClientId($clientId);
        if ($sessionIdAndTimeStamp['session_id']) {
            $searchEvent->setSessionId($sessionIdAndTimeStamp['session_id']);
        }
        if ($sessionIdAndTimeStamp['timestamp']) {
            $searchEvent->setTimestamp($sessionIdAndTimeStamp['timestamp']);
        }
        $searchEvent->setSearchTerm($searchTerm);

        return $searchEvent;
    }

}
