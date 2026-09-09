<?php

namespace WeltPixel\GA4\Model\ServerSide;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use WeltPixel\GA4\Api\ServerSide\ApiInterface;
use WeltPixel\GA4\Helper\ServerSideTracking as GA4Helper;
use WeltPixel\GA4\Helper\BotDetection as BotDetector;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use WeltPixel\GA4\Logger\Logger;
use WeltPixel\GA4\Logger\DebugCollectLogger;

class Api extends \Magento\Framework\Model\AbstractModel implements ApiInterface
{
    /**
     * @var string
     */
    protected $apiEndpoint = 'https://www.google-analytics.com/mp/collect';

    /**
     * @var string
     */
    protected $debugCollectApiEndpoint = 'https://www.google-analytics.com/debug/mp/collect';

    /**
     * @var  GA4Helper
     */
    protected $ga4Helper;

    /**
     * @var BotDetector
     */
    protected $botDetector;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DebugCollectLogger
     */
    protected $debugCollectLogger;

    /**
     * @var string
     */
    protected $measurementId;

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * @var bool
     */
    protected $debugFileMode = false;

    /**
     * @var bool
     */
    protected $debugCollect = false;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param GA4Helper $gaHelper
     * @param CurlFactory $curlFactory
     * @param Json $json
     * @param Logger $logger
     * @param DebugCollectLogger $debugCollectLogger
     * @param BotDetector $botDetector
     */
    public function __construct(
        Context $context,
        Registry $registry,
        GA4Helper $gaHelper,
        CurlFactory $curlFactory,
        Json $json,
        Logger $logger,
        DebugCollectLogger $debugCollectLogger,
        BotDetector $botDetector
    ) {
        parent::__construct($context, $registry);
        $this->ga4Helper = $gaHelper;
        $this->curlFactory = $curlFactory;
        $this->json = $json;
        $this->logger = $logger;
        $this->debugCollectLogger = $debugCollectLogger;
        $this->botDetector = $botDetector;
        $this->measurementId = $this->ga4Helper->getMeasurementId();
        $this->apiSecret = $this->ga4Helper->getApiSecret();
        $this->debugFileMode = $this->ga4Helper->getDebugFileEnabled();
        $this->debugCollect = $this->ga4Helper->getDebugCollectEnabled();
    }

    /**
     * @param array $params
     * @return string
     */
    public function getApiEndpoint($params = [])
    {
        if (!empty($params)) {
            return $this->apiEndpoint . '?' . http_build_query($params);
        }
        return $this->apiEndpoint;
    }

    /**
     * @param array $params
     * @return string
     */
    public function getDebugApiEndpoint($params = [])
    {
        if (!empty($params)) {
            return $this->debugCollectApiEndpoint . '?' . http_build_query($params);
        }
        return $this->debugCollectApiEndpoint;
    }

    /**
     * @return string
     */
    public function getMeasurementId()
    {
        return $this->measurementId;
    }

    /**
     * @param $measurementId
     * @return $this
     */
    public function setMeasurementId($measurementId)
    {
        $this->measurementId = $measurementId;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * @param $measurementId
     * @return $this
     */
    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    /**
     * @return string[]
     */
    protected function getApiUrlParams()
    {
        return [
            'measurement_id' => $this->getMeasurementId(),
            'api_secret' => $this->getApiSecret()
        ];
    }

    /**
     * @param array $params
     * @throws LocalizedException
     */
    protected function _makeApiCall($params = [])
    {
        /** @var \Magento\Framework\HTTP\Client\Curl $curl */
        $curl = $this->curlFactory->create();
        $url = $this->getApiEndpoint($this->getApiUrlParams());
        $payload = $this->json->serialize($params);

        $this->logDebugMessage(__('Api payload:'));
        $this->logDebugMessage($payload);

        $curl->addHeader("Content-Type", "application/json");
        $curl->addHeader("Content-Length", strlen($payload));
        $curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $curl->post($url,
            $payload
        );

        $status = $curl->getStatus();

        if (!in_array($status, range(200, 299))) {
            throw new LocalizedException(__('There was an error with the Api call'));
        }

        if ($this->debugCollect) {
            /** @var \Magento\Framework\HTTP\Client\Curl $curl */
            $curl = $this->curlFactory->create();
            $debugUrl = $this->getDebugApiEndpoint($this->getApiUrlParams());

            $curl->addHeader("Content-Type", "application/json");
            $curl->addHeader("Content-Length", strlen($payload));
            $curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $curl->post($debugUrl,
                $payload
            );

            $this->debugCollectLogger->notice("Status: " . $curl->getStatus());
            $this->debugCollectLogger->notice("Response Body: " . PHP_EOL . $curl->getBody());
        }
    }

    /**
     * @return bool
     */
    protected function isApiPushAllowed()
    {
        return $this->botDetector->isRequestFromBot() === false;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterface $purchaseEvent
     * @return ApiInterface|mixed
     */
    public function pushPurchaseEvent(\WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterface $purchaseEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $purchaseParams = $purchaseEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Purchase event pushed...'));
            $this->_makeApiCall($purchaseParams);
            $this->logDebugMessage(__('Purchase event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Purchase event push error: ') . $ex->getMessage());
        }

        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\RefundInterface $refundEvent
     * @return ApiInterface|mixed
     */
    public function pushRefundEvent(\WeltPixel\GA4\Api\ServerSide\Events\RefundInterface $refundEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $refundParams = $refundEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Refund event pushed...'));
            $this->_makeApiCall($refundParams);
            $this->logDebugMessage(__('Refund event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Refund event push error: ') . $ex->getMessage());
        }

        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SignupInterface $signupEvent
     * @return ApiInterface|mixed
     */
    public function pushSignupEvent(\WeltPixel\GA4\Api\ServerSide\Events\SignupInterface $signupEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $signupParams = $signupEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Signup event pushed...'));
            $this->_makeApiCall($signupParams);
            $this->logDebugMessage(__('Signup event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Signup event push error: ') . $ex->getMessage());
        }

        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\LoginInterface $loginEvent
     * @return ApiInterface|mixed
     */
    public function pushLoginEvent(\WeltPixel\GA4\Api\ServerSide\Events\LoginInterface $loginEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $loginParams = $loginEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Login event pushed...'));
            $this->_makeApiCall($loginParams);
            $this->logDebugMessage(__('Login event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Login event push error: ') . $ex->getMessage());
        }

        return $this;
    }


    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemInterface $viewItemEvent
     * @return ApiInterface|mixed
     */
    public function pushViewItemEvent(\WeltPixel\GA4\Api\ServerSide\Events\ViewItemInterface $viewItemEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $viewItemParams = $viewItemEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('View Item event pushed...'));
            $this->_makeApiCall($viewItemParams);
            $this->logDebugMessage(__('View Item event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('View Item event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewItemListInterface $viewItemListEvent
     * @return \WeltPixel\GA4\Api\ServerSide\ApiInterface|mixed
     */
    public function pushViewItemListEvent(\WeltPixel\GA4\Api\ServerSide\Events\ViewItemListInterface $viewItemListEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $viewItemListParams = $viewItemListEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('View Item List event pushed...'));
            $this->_makeApiCall($viewItemListParams);
            $this->logDebugMessage(__('View Item List event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('View Item List event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SelectItemInterface $selectItemEvent
     * @return ApiInterface|mixed
     */
    public function pushSelectItemEvent(\WeltPixel\GA4\Api\ServerSide\Events\SelectItemInterface $selectItemEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $selectItemParams = $selectItemEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Select Item event pushed...'));
            $this->_makeApiCall($selectItemParams);
            $this->logDebugMessage(__('Select Item event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Select Item event push error: ') . $ex->getMessage());
        }
        return $this;
    }


    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SearchInterface $searchEvent
     * @return ApiInterface|mixed
     */
    public function pushSearchEvent(\WeltPixel\GA4\Api\ServerSide\Events\SearchInterface $searchEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $searchParams = $searchEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Search event pushed...'));
            $this->_makeApiCall($searchParams);
            $this->logDebugMessage(__('Search event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Search event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToCartInterface $addToCartEvent
     * @return ApiInterface|mixed
     */
    public function pushAddToCartEvent(\WeltPixel\GA4\Api\ServerSide\Events\AddToCartInterface $addToCartEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $addToCartParams = $addToCartEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Add To Cart event pushed...'));
            $this->_makeApiCall($addToCartParams);
            $this->logDebugMessage(__('Add To Cart event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Add To Cart event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartInterface $removeFromCartEvent
     * @return ApiInterface|mixed
     */
    public function pushRemoveFromCartEvent(\WeltPixel\GA4\Api\ServerSide\Events\RemoveFromCartInterface $removeFromCartEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $removeFromCartParams = $removeFromCartEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Remove From Cart event pushed...'));
            $this->_makeApiCall($removeFromCartParams);
            $this->logDebugMessage(__('Remove From Cart event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Remove From Cart event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\ViewCartInterface $viewCartEvent
     * @return ApiInterface|mixed
     */
    public function pushViewCartEvent(\WeltPixel\GA4\Api\ServerSide\Events\ViewCartInterface $viewCartEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $viewCartParams = $viewCartEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('View Cart event pushed...'));
            $this->_makeApiCall($viewCartParams);
            $this->logDebugMessage(__('View Cart event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('View Cart event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutInterface $beginCheckoutEvent
     * @return ApiInterface|mixed
     */
    public function pushBeginCheckoutEvent(\WeltPixel\GA4\Api\ServerSide\Events\BeginCheckoutInterface $beginCheckoutEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $beginCheckoutParams = $beginCheckoutEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Begin Checkout event pushed...'));
            $this->_makeApiCall($beginCheckoutParams);
            $this->logDebugMessage(__('Begin Checkout event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Begin Checkout event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddPaymentInfoInterface $addPaymentInfoEvent
     * @return ApiInterface|mixed
     */
    public function pushAddPaymentInfoEvent($addPaymentInfoEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $addPaymentInfoParams = $addPaymentInfoEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Add Payment Info event pushed...'));
            $this->_makeApiCall($addPaymentInfoParams);
            $this->logDebugMessage(__('Add Payment Info event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Add Payment Info event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddShippingInfoInterface $addShippingInfoEvent
     * @return ApiInterface|mixed
     */
    public function pushAddShippingInfoEvent($addShippingInfoEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $addShippingInfoParams = $addShippingInfoEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Add Shipping Info event pushed...'));
            $this->_makeApiCall($addShippingInfoParams);
            $this->logDebugMessage(__('Add Shipping Info event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Add Shipping Info event push error: ') . $ex->getMessage());
        }
        return $this;
    }

    /**
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistInterface $addToWishlistEvent
     * @return ApiInterface|mixed
     */
    public function pushAddToWishlistEvent($addToWishlistEvent)
    {
        if (!$this->isApiPushAllowed()) {
            return $this;
        }
        $addShippingInfoParams = $addToWishlistEvent->getParams($this->debugCollect);
        try {
            $this->logDebugMessage(__('Add To Wishlist event pushed...'));
            $this->_makeApiCall($addShippingInfoParams);
            $this->logDebugMessage(__('Add To Wishlist event pushed successfully.'));
        } catch (\Exception $ex) {
            $this->logger->error(__('Add To Wishlist event push error: ') . $ex->getMessage());
        }
        return $this;
    }


    /**
     * @param $msg
     */
    protected function logDebugMessage($msg)
    {
        if ($this->debugFileMode) {
            $this->logger->notice($msg);
        }
        if ($this->debugCollect) {
            $this->debugCollectLogger->notice($msg);
        }
    }
}
