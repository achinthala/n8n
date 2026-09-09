<?php
namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class CartUpdateItemOptionsAfter implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4ServerSideHelper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartBuilderInterface */
    protected $removeFromCartBuilder;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface */
    protected $addToCartCartBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;


    /**
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartBuilderInterface $removeFromCartBuilder
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface $addToCartCartBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     */
    public function __construct(
        \WeltPixel\GA4\Helper\Data $helper,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper,
        \Magento\Checkout\Model\Session $_checkoutSession,
        \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartBuilderInterface $removeFromCartBuilder,
        \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface $addToCartCartBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
    )
    {
        $this->helper = $helper;
        $this->ga4ServerSideHelper = $ga4ServerSideHelper;
        $this->_checkoutSession = $_checkoutSession;
        $this->removeFromCartBuilder = $removeFromCartBuilder;
        $this->addToCartCartBuilder = $addToCartCartBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
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

        $item = $observer->getData('item');

        if ($item->getQtyBeforeChange() != $item->getQty()) {
            $qtyChange =  $item->getQty() - $item->getQtyBeforeChange();
            if ($qtyChange != 0) {
                if ($qtyChange < 0) {
                    $serverSideRemoveFromCartEnabled = $this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_REMOVE_FROM_CART);
                    if ($serverSideRemoveFromCartEnabled) {
                        $removeFromCartEvent = $this->removeFromCartBuilder->getRemoveFromCartEvent($item->getProduct(), abs($qtyChange), $item);
                        $this->ga4ServerSideApi->pushRemoveFromCartEvent($removeFromCartEvent);
                    }
                    if (!$serverSideRemoveFromCartEnabled || !$this->ga4ServerSideHelper->isDataLayerEventDisabled()) {
                        $currentRemoveToCartData = $this->_checkoutSession->getGA4RemoveFromCartData();
                        $removeFromCartPushData = $this->helper->removeFromCartPushData(abs($qtyChange), $item->getProduct(), $item);

                        $newRemoveFromCartPushData = $this->helper->mergeAddToCartPushData($currentRemoveToCartData, $removeFromCartPushData);
                        $this->_checkoutSession->setGA4RemoveFromCartData($newRemoveFromCartPushData);
                    }
                } else {
                    $serverSideAddToCartEnabled = $this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_CART);
                    if ($serverSideAddToCartEnabled) {
                        $addToCartEvent = $this->addToCartCartBuilder->getAddToCartEvent($item->getProduct(), $qtyChange,  $item->getBuyRequest()->getData(), true);
                        $this->ga4ServerSideApi->pushAddToCartEvent($addToCartEvent);
                    }
                    if (!$serverSideAddToCartEnabled || !$this->ga4ServerSideHelper->isDataLayerEventDisabled()) {
                        $currentAddToCartData = $this->_checkoutSession->getGA4AddToCartData();
                        $addToCartPushData = $this->helper->addToCartPushData($qtyChange, $item->getProduct(),  $item->getBuyRequest()->getData(), true);

                        $newAddToCartPushData = $this->helper->mergeAddToCartPushData($currentAddToCartData, $addToCartPushData);
                        $this->_checkoutSession->setGA4AddToCartData($newAddToCartPushData);
                    }
                }
            }
        }

        return $this;
    }
}
