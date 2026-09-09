<?php

namespace WeltPixel\GA4\Plugin;

class PaymentInformation
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4ServerSideHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \WeltPixel\GA4\Helper\Data $helper,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository)
    {
        $this->helper = $helper;
        $this->ga4ServerSideHelper = $ga4ServerSideHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @return int Order ID.
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        $result
        )
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        if (($this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_PAYMENT_INFO)
            && $this->ga4ServerSideHelper->isDataLayerEventDisabled())) {
            return $result;
        }

        $orderId = $result;

        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order->getId()) {
            try {
                $order = $this->orderRepository->get($orderId);
            } catch (\Exception $ex) {
                return $result;
            }
        }

        try {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();

            if ($additionalInformation && isset($additionalInformation['method_title'])) {
                $paymentMethodTitle = $additionalInformation['method_title'];
                $this->_checkoutSession->setGA4CheckoutPaymentData($this->helper->addCheckoutStepPushData('2', $paymentMethodTitle));
            }
        } catch (\Exception $ex) {
            return $result;
        }

        return $result;
    }


}
