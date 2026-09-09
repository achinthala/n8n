<?php
namespace WeltPixel\GA4\Block\Track;

/**
 * Class \WeltPixel\GA4\Block\Track\Cart
 */
class Cart extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\ViewCartBuilderInterface */
    protected $viewCartBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;


    /**
     * @pqram \WeltPixel\GA4\Api\ServerSide\Events\ViewCartBuilderInterface $viewCartBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \WeltPixel\GA4\Api\ServerSide\Events\ViewCartBuilderInterface $viewCartBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->viewCartBuilder = $viewCartBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        parent::__construct($context, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function pushViewCartEvent()
    {
        $quote = $this->checkoutSession->getQuote();

        $viewCartEvent = $this->viewCartBuilder->getViewCartEvent($quote);
        $this->ga4ServerSideApi->pushViewCartEvent($viewCartEvent);

    }

}
