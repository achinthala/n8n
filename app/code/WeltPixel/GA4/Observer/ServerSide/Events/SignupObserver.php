<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class SignupObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\SignupBuilderInterface */
    protected $signupBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SignupBuilderInterface $signupBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\SignupBuilderInterface $signupBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->signupBuilder = $signupBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_SIGNUP)) {

            $customerDataObject = $observer->getData('customer_data_object') ?? false;
            $origCustomerDataObject = $observer->getData('orig_customer_data_object') ?? false;

            if (!$origCustomerDataObject) {
                $customerId = $customerDataObject->getId();
                $signupEvent = $this->signupBuilder->getSignupEvent($customerId);
                $this->ga4ServerSideApi->pushSignupEvent($signupEvent);
            }
        }

        if (!($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_SIGNUP)
            && $this->ga4Helper->isDataLayerEventDisabled())) {
            $this->customerSession->setGA4SignupData([
                'event' => 'sign_up',
                'ecommerce' => [
                    'method' => 'Magento',
                ]
            ]);
        }

        return $this;
    }
}
