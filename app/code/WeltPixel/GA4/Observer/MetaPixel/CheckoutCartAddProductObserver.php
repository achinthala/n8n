<?php
namespace WeltPixel\GA4\Observer\MetaPixel;

use Magento\Framework\Event\ObserverInterface;

class CheckoutCartAddProductObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\MetaPixelTracking
     */
    protected $metaPixelHelper;


    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;


    /**
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelHelper
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(\WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelHelper,
                                \Magento\Checkout\Model\Session $_checkoutSession,
                                \Magento\Framework\Locale\ResolverInterface $localeResolver)
    {
        $this->metaPixelHelper = $metaPixelHelper;
        $this->_checkoutSession = $_checkoutSession;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->metaPixelHelper->isMetaPixelTrackingEnabled() || !$this->metaPixelHelper->shouldMetaPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\MetaPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            return $this;
        }

        $product = $observer->getData('product');
        $request = $observer->getData('request');

        $params = $request->getParams();

        if (isset($params['qty'])) {
            $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                ['locale' => $this->localeResolver->getLocale()]
            );
            $qty = $filter->filter($params['qty']);
        } else {
            $qty = 1;
        }

        $addToCartPushData = $this->metaPixelHelper->metaPixelAddToCartPushData($product, $qty);
        $initialAddTocartPushData =  $this->_checkoutSession->getMetaPixelAddToCartData() ?? [];
        $initialAddTocartPushData[] = $addToCartPushData;
        $this->_checkoutSession->setMetaPixelAddToCartData($initialAddTocartPushData);

        return $this;
    }
}
