<?php
namespace WeltPixel\GA4\Controller\Track;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;


class Product extends Action
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface */
    protected $viewItemBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /** @var \WeltPixel\GA4\Helper\ConfigurableProducts */
    protected $configurableProductsHelper;

    /**
     * @param Context $context
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface $viewItemBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \WeltPixel\GA4\Helper\ConfigurableProducts $configurableProductsHelper
     */
    public function __construct(
        Context $context,
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\ViewItemBuilderInterface $viewItemBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \WeltPixel\GA4\Helper\ConfigurableProducts $configurableProductsHelper
    ) {
        parent::__construct($context);
        $this->ga4Helper = $ga4Helper;
        $this->viewItemBuilder = $viewItemBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->configurableProductsHelper = $configurableProductsHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $productId = $this->getRequest()->getPostValue('product_id');
        $productTypeId = $this->getRequest()->getPostValue('product_type', '');

        if (!$productId) {
            return $this->prepareResult('');
        }

        if (!$this->ga4Helper->isServerSideTrakingEnabled() || !$this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_VIEW_ITEM)) {
            return $this->prepareResult('');
        }

        $sendOnlyMainProduct = true;
        if (($productTypeId  ==  \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
            && ($this->ga4Helper->getParentOrChildIdUsage() == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD)
            && $this->ga4Helper->sendAllChildConfigurableProducts()) {
            $sendOnlyMainProduct = false;
        }

        if ($sendOnlyMainProduct) {
            $viewItemEvent = $this->viewItemBuilder->getViewItemEvent($productId);
            $this->ga4ServerSideApi->pushViewItemEvent($viewItemEvent);
            return $this->prepareResult('');
        }

        $product = $this->configurableProductsHelper->getProductById($productId);
        if ($product) {
            $configurableOptions = $product->getTypeInstance()->getConfigurableOptions($product);
            $isVariantEnabled = $this->ga4Helper->isVariantEnabled();
            $_children = $product->getTypeInstance()->getSalableUsedProducts($product);
            $productsArray = [];
            foreach ($_children as $child) {
                $variant = '';
                if ($isVariantEnabled) {
                    $variant = $this->configurableProductsHelper->getVariantForSimpleProduct($child, $configurableOptions);
                }
                $productsArray[] = [
                    'variant' => $variant,
                    'product_id' => $child->getId()
                ];
            }

            $viewItemEvent = $this->viewItemBuilder->getViewItemEventWithMultipleProducts($product, $productsArray);
            $this->ga4ServerSideApi->pushViewItemEvent($viewItemEvent);
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
