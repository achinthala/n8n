<?php
namespace WeltPixel\GA4\Controller\Track;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Childproduct extends Action
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface */
    protected $viewItemBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @param Context $context
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface $viewItemBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     */
    public function __construct(
        Context $context,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface $viewItemBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
    ) {
        parent::__construct($context);
        $this->ga4Helper = $ga4Helper;
        $this->viewItemBuilder = $viewItemBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $productId = $this->getRequest()->getPostValue('product_id');
        $parentProductId = $this->getRequest()->getPostValue('parent_product_id', false);
        $viewItemEvent = null;
        $result = '';

        if (!$productId) {
            return $this->prepareResult('');
        }
        if (!$parentProductId) {
            $parentProductId = $productId;
        }

        $variant = $this->getRequest()->getPostValue('variant');
        if ($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_VIEW_ITEM)) {
            $viewItemEvent = $this->viewItemBuilder->getViewItemEventWithMultipleProducts(
                $parentProductId, [
                    [
                        'product_id' =>  $productId,
                        'variant' => $variant
                    ]
                ]);
            $this->ga4ServerSideApi->pushViewItemEvent($viewItemEvent);
        }

        if (!($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_VIEW_ITEM)
            && $this->ga4Helper->isDataLayerEventDisabled())) {
            $viewItemEvent = $this->viewItemBuilder->getViewItemEventWithMultipleProducts(
                $parentProductId, [
                    [
                        'product_id' =>  $productId,
                        'variant' => $variant
                    ]
            ]);

            $viewItemEventData = $viewItemEvent->getParams();
            if ($viewItemEventData && isset($viewItemEventData['events'])) {
               $ecommerceData = $viewItemEventData['events'][0]['params'];
                unset($ecommerceData['page_location']);

               $result = [
                   'ecommerce' => $ecommerceData,
                   'event' => 'view_item'
               ];
            }
        }

        return $this->prepareResult($result);
    }

    /**
     * @param array $result
     * @return string
     */
    protected function prepareResult($result)
    {
        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
