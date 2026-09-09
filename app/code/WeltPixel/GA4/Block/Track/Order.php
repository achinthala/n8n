<?php
namespace WeltPixel\GA4\Block\Track;

/**
 * Class \WeltPixel\GA4\Block\Track\Order
 */
class Order extends \Magento\Framework\View\Element\Template
{
    /** @var \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface */
    protected $purchaseBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /** @var \WeltPixel\GA4\Helper\ServerSideTracking */
    protected $ga4ServerSideHelper;


    /**
     * @pqram \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper
     * @param array $data
     */
    public function __construct(
        \WeltPixel\GA4\Api\ServerSide\Events\PurchaseBuilderInterface $purchaseBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Framework\View\Element\Template\Context $context,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper,
        array $data = []
    )
    {
        $this->purchaseBuilder = $purchaseBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->ga4ServerSideHelper = $ga4ServerSideHelper;
        parent::__construct($context, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function pushPurchaseEvent()
    {
        $order = $this->getOrder();
        if ($order && $this->ga4ServerSideHelper->isOrderTrackingAllowedBasedOnOrderStatus($order)) {
            $purchaseEvent = $this->purchaseBuilder->getPurchaseEvent($order);
            $this->ga4ServerSideApi->pushPurchaseEvent($purchaseEvent);
        }
    }
}
