<?php

namespace WeltPixel\GA4\Plugin\ServerSide;

class ShippingInformation
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoBuilderInterface */
    protected $addShippingInfoBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoBuilderInterface $addShippingInfoBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoBuilderInterface $addShippingInfoBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi)
    {
        $this->ga4Helper = $ga4Helper;
        $this->quoteRepository = $quoteRepository;
        $this->addShippingInfoBuilder = $addShippingInfoBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $addressInformation
    ) {

        $result = $proceed($cartId, $addressInformation);

        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_SHIPPING_INFO)) {
            return $result;
        }

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->quoteRepository->getActive($cartId);
            $shippingDescription = $quote->getShippingAddress()->getShippingDescription();

            $addShippingInfoEvent = $this->addShippingInfoBuilder->getAddShippingInfoEvent($quote, $shippingDescription);
            $this->ga4ServerSideApi->pushAddShippingInfoEvent($addShippingInfoEvent);
        } catch (\Exception $ex) {
            return $result;
        }

        return $result;
    }

}
