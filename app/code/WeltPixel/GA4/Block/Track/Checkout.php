<?php
namespace WeltPixel\GA4\Block\Track;

/**
 * Class \WeltPixel\GA4\Block\Track\Checkout
 */
class Checkout extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutBuilderInterface */
    protected $beginCheckoutBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;


    /**
     * @pqram \WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutBuilderInterface $beginCheckoutBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutBuilderInterface $beginCheckoutBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->beginCheckoutBuilder = $beginCheckoutBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        parent::__construct($context, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function pushBeginCheckoutEvent()
    {
        $quote = $this->checkoutSession->getQuote();

        $beginCheckoutEvent = $this->beginCheckoutBuilder->getBeginCheckoutEvent($quote);
        $this->ga4ServerSideApi->pushBeginCheckoutEvent($beginCheckoutEvent);

    }

}
