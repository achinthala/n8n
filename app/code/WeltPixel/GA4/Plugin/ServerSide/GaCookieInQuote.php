<?php

namespace WeltPixel\GA4\Plugin\ServerSide;

use Magento\Quote\Model\Quote;

class GaCookieInQuote
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
        )
    {
        $this->ga4Helper = $ga4Helper;
    }

    /**
     * @param \Magento\Checkout\Model\Session $subject
     * @param $result
     * @return Quote
     */
    public function afterGetQuote(
        \Magento\Checkout\Model\Session $subject,
        $result)
    {

        if (!$this->ga4Helper->isServerSideTrakingEnabled()) {
            return $result;
        }

        if ($result) {
            $clientId = $this->ga4Helper->getClientId();
            $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();
            $result->setData('ga_cookie', $clientId);
            if ($sessionIdAndTimeStamp['session_id']) {
                $result->setData('ga_session_id', $sessionIdAndTimeStamp['session_id']);
            }
            if ($sessionIdAndTimeStamp['timestamp']) {
                $result->setData('ga_timestamp', $sessionIdAndTimeStamp['timestamp']);
            }
        }

        return $result;
    }


}
