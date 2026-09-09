<?php
namespace WeltPixel\GA4\Observer\ServerSide;

use Magento\Framework\Event\ObserverInterface;

class AddUpdateHandlesObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->request = $request;
    }

    /**
     * Add Custom layout handle
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getData('layout');

        /** Apply only on pages where page is rendered */
        $currentHandles = $layout->getUpdate()->getHandles();
        if (!in_array('default', $currentHandles)) {
            return $this;
        }

        $requestPath = $this->request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->request->getControllerName() .
            DIRECTORY_SEPARATOR . $this->request->getActionName();

        $requestPathDynamic = $this->request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->request->getControllerName() .
            DIRECTORY_SEPARATOR . '*';

        $customCheckoutPagePaths = trim($this->ga4Helper->getCustomCheckoutPagePaths());

        if (strlen($customCheckoutPagePaths)) {
            $customCheckoutPagePaths = explode(",", $customCheckoutPagePaths);
            $customCheckoutPagePaths = array_map('trim', $customCheckoutPagePaths);

            if (in_array($requestPath, $customCheckoutPagePaths) || in_array($requestPathDynamic, $customCheckoutPagePaths)) {
                $layout->getUpdate()->addHandle('weltpixel_ga4_checkout_begin');
            }
        }

        $successPagePaths = [
            'checkout/onepage/success'
        ];

        $customSuccessPagePaths = trim($this->ga4Helper->getCustomSuccessPagePaths());

        if (strlen($customSuccessPagePaths)) {
            $successPagePaths = array_merge($successPagePaths, array_map('trim', explode(",", $customSuccessPagePaths)));
        }

        if (in_array($requestPath, $successPagePaths) || in_array($requestPathDynamic, $successPagePaths)) {
            $layout->getUpdate()->addHandle('weltpixel_ga4_checkout_success');
        }


        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_SELECT_ITEM)) {
            return $this;
        }

        $layout->getUpdate()->addHandle('weltpixel_ga4_serverside_select_item');

        return $this;
    }
}
