<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class AddToCartObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface */
    protected $addToCartBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface $addToCartBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\AddToCartBuilderInterface $addToCartBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->addToCartBuilder = $addToCartBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->localeResolver = $localeResolver;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_checkoutSession->setAddProductServerSideTrigger(true);
        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_CART)) {
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

        if ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            $superGroup = $params['super_group'];
            $superGroup = is_array($superGroup) ? array_filter($superGroup, 'intval') : [];


            $associatedProducts =  $product->getTypeInstance()->getAssociatedProducts($product);
            foreach ($associatedProducts as $associatedProduct) {
                if (isset($superGroup[$associatedProduct->getId()]) && ($superGroup[$associatedProduct->getId()] > 0) ) {
                    $addToCartEvent = $this->addToCartBuilder->getAddToCartEvent($associatedProduct, $superGroup[$associatedProduct->getId()]);
                    $this->ga4ServerSideApi->pushAddToCartEvent($addToCartEvent);
                }
            }
        } else {
            $displayOption = $this->ga4Helper->getParentOrChildIdUsage();
            $requestParams = [];
            if ( ($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) && ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)) {
                $params['qty'] = $qty;
                $requestParams = $params;
            }
            $addToCartEvent = $this->addToCartBuilder->getAddToCartEvent($product, $qty, $requestParams);
            $this->ga4ServerSideApi->pushAddToCartEvent($addToCartEvent);
        }

        return $this;
    }
}
