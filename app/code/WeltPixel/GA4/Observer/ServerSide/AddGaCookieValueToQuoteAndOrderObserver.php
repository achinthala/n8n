<?php
namespace WeltPixel\GA4\Observer\ServerSide;

use Magento\Framework\Event\ObserverInterface;

class AddGaCookieValueToQuoteAndOrderObserver implements ObserverInterface
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
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->ga4Helper->isServerSideTrakingEnabled()) {
            return $this;
        }

        $quote = $observer->getData('quote') ?? false;
        $order = $observer->getData('order') ?? false;

        $clientId = $this->ga4Helper->getClientId();
        $sessionIdAndTimeStamp = $this->ga4Helper->getSessionIdAndTimeStamp();

        if ($quote) {
            $quote->setData('ga_cookie', $clientId);
            if ($sessionIdAndTimeStamp['session_id']) {
                $quote->setData('ga_session_id', $sessionIdAndTimeStamp['session_id']);
            }
            if ($sessionIdAndTimeStamp['timestamp']) {
                $quote->setData('ga_timestamp', $sessionIdAndTimeStamp['timestamp']);
            }
        }

        if ($order) {
            $order->setData('ga_cookie', $clientId);
            if ($sessionIdAndTimeStamp['session_id']) {
                $order->setData('ga_session_id', $sessionIdAndTimeStamp['session_id']);
            }
            if ($sessionIdAndTimeStamp['timestamp']) {
                $order->setData('ga_timestamp', $sessionIdAndTimeStamp['timestamp']);
            }
        }

        return $this;
    }
}
