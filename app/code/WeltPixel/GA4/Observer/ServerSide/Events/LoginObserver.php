<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class LoginObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\LoginBuilderInterface */
    protected $loginBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\LoginBuilderInterface $loginBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\LoginBuilderInterface $loginBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->loginBuilder = $loginBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_LOGIN)) {
            $customer = $observer->getData('customer') ?? false;

            if ($customer) {
                $customerId = $customer->getId();
                $loginEvent = $this->loginBuilder->getLoginEvent($customerId);
                $this->ga4ServerSideApi->pushLoginEvent($loginEvent);
            }
        }

        if (!($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_LOGIN)
            && $this->ga4Helper->isDataLayerEventDisabled())) {
                $this->customerSession->setGA4LoginData([
                    'event' => 'login',
                    'ecommerce' => [
                        'method' => 'Magento',
                    ]
                ]);
        }

        return $this;
    }
}
