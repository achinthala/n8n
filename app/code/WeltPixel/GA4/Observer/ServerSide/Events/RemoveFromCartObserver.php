<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class RemoveFromCartObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartBuilderInterface */
    protected $removeFromCartBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;


    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \Magento\Catalog\Model\ProductRepository $productRepository,
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartBuilderInterface $removeFromCartBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     */
    public function __construct(\WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
                                \Magento\Catalog\Model\ProductRepository $productRepository,
                                \Magento\Checkout\Model\Session $_checkoutSession,
                                \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartBuilderInterface $removeFromCartBuilder,
                                \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi)
    {
        $this->ga4Helper = $ga4Helper;
        $this->_checkoutSession = $_checkoutSession;
        $this->productRepository = $productRepository;
        $this->removeFromCartBuilder = $removeFromCartBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $addProductTrigger = $this->_checkoutSession->getAddProductServerSideTrigger();
        $this->_checkoutSession->setAddProductServerSideTrigger(false);

        if ($addProductTrigger) {
            return $this;
        }

        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_REMOVE_FROM_CART)) {
            return $this;
        }

        $quoteItem = $observer->getData('quote_item');
        $productId = $quoteItem->getData('product_id');

        if (!$productId) {
            return $this;
        }

        $product = $this->productRepository->getById($productId);
        $qty = $quoteItem->getData('qty');

        $removeFromCartEvent = $this->removeFromCartBuilder->getRemoveFromCartEvent($product, $qty, $quoteItem);
        $this->ga4ServerSideApi->pushRemoveFromCartEvent($removeFromCartEvent);

        return $this;
    }
}
