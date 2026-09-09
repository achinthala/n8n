<?php
namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteRemoveItemObserver implements ObserverInterface
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
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;


    /**
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper
     * @param \Magento\Catalog\Model\ProductRepository $productRepository,
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     */
    public function __construct(\WeltPixel\GA4\Helper\Data $helper,
                                \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper,
                                \Magento\Catalog\Model\ProductRepository $productRepository,
                                \Magento\Checkout\Model\Session $_checkoutSession)
    {
        $this->helper = $helper;
        $this->ga4ServerSideHelper = $ga4ServerSideHelper;
        $this->_checkoutSession = $_checkoutSession;
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $addProductTrigger = $this->_checkoutSession->getAddProductTrigger();
        $this->_checkoutSession->setAddProductTrigger(false);

        if ($addProductTrigger) {
            return $this;
        }

        if (!$this->helper->isEnabled()) {
            return $this;
        }

        if (($this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_REMOVE_FROM_CART)
            && $this->ga4ServerSideHelper->isDataLayerEventDisabled())) {
            return $this;
        }

        $quoteItem = $observer->getData('quote_item');
        $productId = $quoteItem->getData('product_id');

        if (!$productId) {
            return $this;
        }

        $product = $this->productRepository->getById($productId);
        $qty = $quoteItem->getData('qty');

        /** Need to extend or use another event or plugin to send variant */
        $this->_checkoutSession->setGA4RemoveFromCartData($this->helper->removeFromCartPushData($qty, $product, $quoteItem));

        return $this;
    }
}
