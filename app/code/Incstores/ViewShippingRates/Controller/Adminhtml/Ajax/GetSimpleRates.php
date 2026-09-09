<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Incstores\ViewShippingRates\Model\Request\SimpleRequestTransformer;
use Incstores\ViewShippingRates\Model\Response\SimpleResponseTransformer;
use Incstores\ViewShippingRates\Model\Api\SimpleShippingRatesApi;
use Psr\Log\LoggerInterface;

class GetSimpleRates extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Incstores_ViewShippingRates::shipping_rates';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var SimpleRequestTransformer
     */
    private $requestTransformer;

    /**
     * @var SimpleResponseTransformer
     */
    private $responseTransformer;

    /**
     * @var SimpleShippingRatesApi
     */
    private $shippingRatesApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SimpleRequestTransformer $requestTransformer
     * @param SimpleResponseTransformer $responseTransformer
     * @param SimpleShippingRatesApi $shippingRatesApi
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SimpleRequestTransformer $requestTransformer,
        SimpleResponseTransformer $responseTransformer,
        SimpleShippingRatesApi $shippingRatesApi,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->requestTransformer = $requestTransformer;
        $this->responseTransformer = $responseTransformer;
        $this->shippingRatesApi = $shippingRatesApi;
        $this->logger = $logger;
    }

    /**
     * Execute action to get simple shipping rates
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $requestData = $this->getRequest()->getPostValue();

            if (empty($requestData)) {
                throw new LocalizedException(__('Invalid request data'));
            }

            // Log the incoming request for debugging
            $this->logger->info('GetSimpleRates - Request received', [
                'request_type' => $requestData['request_type'] ?? 'unknown',
                'ship_to_zipcode' => $requestData['ship_to_zipcode'] ?? 'missing',
                'ship_from_zipcode' => $requestData['ship_from_zipcode'] ?? 'missing'
            ]);

            // Validate that this is indeed a simple request
            if (($requestData['request_type'] ?? '') !== 'simple') {
                throw new LocalizedException(__('Invalid request type for simple rates endpoint'));
            }

            // Transform form data to API format
            $apiRequestData = $this->requestTransformer->transform($requestData);

            $this->logger->info('GetSimpleRates - Transformed request data', [
                'pickup_zipcode' => $apiRequestData['pickupFrom']['address']['zipCode'] ?? 'missing',
                'delivery_zipcode' => $apiRequestData['deliverTo']['zipCode'] ?? 'missing',
                'weight_class' => $apiRequestData['weightClass'] ?? 'missing'
            ]);

             // Call the shipping rates API
            $rawApiResponse = $this->shippingRatesApi->getSimpleRates($apiRequestData);

            // Transform API response to standardized format
            $transformedResponse = $this->responseTransformer->transform($rawApiResponse);

            // Add debug information for ship-to address display
            $transformedResponse['debugInfo'] = [
                'transformedApiRequest' => $apiRequestData,
                'validationErrors' => []
            ];

            $this->logger->info('GetSimpleRates - API Request Data', [
                'full_api_request' => $apiRequestData
            ]);

            $this->logger->info('GetSimpleRates - API Response', [
                'full_api_response' => $rawApiResponse
            ]);

            $this->logger->info('GetSimpleRates - Response processed', [
                'successful' => $transformedResponse['isSuccessful'],
                'shipment_count' => count($transformedResponse['lineItemShipments'])
            ]);

            return $resultJson->setData([
                'success' => true,
                'data' => $transformedResponse
            ]);

        } catch (LocalizedException $e) {
            $this->logger->error('GetSimpleRates - LocalizedException: ' . $e->getMessage());

            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('GetSimpleRates - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while processing your simple shipping request.')
            ]);
        }
    }
}
