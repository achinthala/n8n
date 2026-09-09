<?php
namespace WeltPixel\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddUpdateHandlesObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $ga4Helper;

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \WeltPixel\GA4\Helper\Data $ga4Helper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \WeltPixel\GA4\Helper\Data $ga4Helper,
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

        if ($this->ga4Helper->isDatalayerPreviewEnabled() && $this->ga4Helper->isDatalayerPreviewIpRestrictionEnabled()) {
            $layout->getUpdate()->addHandle('weltpixel_ga4_datalayer_preview');
        }

        return $this;
    }
}
