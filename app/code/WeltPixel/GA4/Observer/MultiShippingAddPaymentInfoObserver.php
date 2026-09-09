<?php
namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class MultiShippingAddPaymentInfoObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $ga4Helper;

    /**
     * @var  \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Helper\Data $helper,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->helper = $helper;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        if (($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_PAYMENT_INFO)
            && $this->ga4Helper->isDataLayerEventDisabled())) {
            return $this;
        }

        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();
        try {
            $paymentCode = $quote->getPayment()->getMethod();

            $allPaymentMethodsArray = $this->paymentHelper->getPaymentMethodList();
            if (isset($allPaymentMethodsArray[$paymentCode])) {
                $paymentMethodTitle = $allPaymentMethodsArray[$paymentCode];
                $this->helper->setGA4MultipleCheckoutPaymentData($this->helper->addCheckoutStepPushData('2', $paymentMethodTitle, $order));
            }
        } catch (\Exception $ex) {
            return $this;
        }

        return $this;
    }
}
