<?php
namespace WeltPixel\GA4\Controller\Track;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;


class Viewitemlist extends Action
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\ViewItemListBuilderInterface */
    protected $viewItemListBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /** @var \WeltPixel\GA4\Model\ServerSide\JsonBuilder */
    protected $ga4ServerSideJsonBuilder;

    /**
     * @param Context $context
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemListBuilderInterface $viewItemListBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \WeltPixel\GA4\Model\ServerSide\JsonBuilder $ga4ServerSideJsonBuilder
     */
    public function __construct(
        Context $context,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\ViewItemListBuilderInterface $viewItemListBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \WeltPixel\GA4\Model\ServerSide\JsonBuilder $ga4ServerSideJsonBuilder
    ) {
        parent::__construct($context);
        $this->ga4Helper = $ga4Helper;
        $this->viewItemListBuilder = $viewItemListBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->ga4ServerSideJsonBuilder = $ga4ServerSideJsonBuilder;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $hashId = $this->getRequest()->getPostValue('hash_id', 0);

        if (!$hashId) {
            return $this->prepareResult('');
        }

        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_VIEW_ITEM_LIST)) {
            return $this->prepareResult('');
        }

        //fetch based on the hash the json, iterate through it and push data
        $viewItemListParams = $this->ga4ServerSideJsonBuilder->getContentFromFile($hashId);
        $viewItemListOptions = json_decode($viewItemListParams,true);

        if ($viewItemListOptions) {
            foreach ($viewItemListOptions as $viewItemListOptionParams) {
                if (isset($viewItemListOptionParams['params'])) {
                    $viewItemListEvent = $this->viewItemListBuilder->getViewItemListEvent($viewItemListOptionParams['params']);
                    $this->ga4ServerSideApi->pushViewItemListEvent($viewItemListEvent);
                }
            }
        }
        return $this->prepareResult('');
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
