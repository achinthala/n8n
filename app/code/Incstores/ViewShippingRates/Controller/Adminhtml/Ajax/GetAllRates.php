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
use Incstores\ViewShippingRates\Model\Request\AllRequestTransformer;
use Incstores\ViewShippingRates\Model\Response\AllResponseTransformer;
use Incstores\ViewShippingRates\Model\Api\AllShippingRatesApi;
use Psr\Log\LoggerInterface;

class GetAllRates extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Incstores_ViewShippingRates::shipping_rates';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var AllRequestTransformer
     */
    private $requestTransformer;

    /**
     * @var AllResponseTransformer
     */
    private $responseTransformer;

    /**
     * @var AllShippingRatesApi
     */
    private $allShippingRatesApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AllRequestTransformer $requestTransformer
     * @param AllResponseTransformer $responseTransformer
     * @param AllShippingRatesApi $allShippingRatesApi
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AllRequestTransformer $requestTransformer,
        AllResponseTransformer $responseTransformer,
        AllShippingRatesApi $allShippingRatesApi,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->requestTransformer = $requestTransformer;
        $this->responseTransformer = $responseTransformer;
        $this->allShippingRatesApi = $allShippingRatesApi;
        $this->logger = $logger;
    }

    /**
     * Execute action to get all shipping rates (product/quote requests)
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
            $this->logger->info('GetAllRates - Request received', [
                'request_type' => $requestData['request_type'] ?? 'unknown',
                'ship_to_zipcode' => $requestData['ship_to_zipcode'] ?? 'missing'
            ]);

            // Validate that this is a product or quote request
            $requestType = $requestData['request_type'] ?? '';
            if (!in_array($requestType, ['product', 'quote'])) {
                throw new LocalizedException(__('Invalid request type for all rates endpoint: %1', $requestType));
            }
            
            // Transform form data to API format
            $apiRequestData = $this->requestTransformer->transform($requestData);
            
            $this->logger->info('GetAllRates - Transformed request data', [
                'request_type' => $requestType,
                'transaction_id' => $apiRequestData['transactionId'] ?? 'none',
                'line_items_count' => count($apiRequestData['lineItems'] ?? [])
            ]);
            
            // Call the real shipping rates API for product/quote requests
            $rawApiResponse = $this->allShippingRatesApi->getAllRates($apiRequestData);
            
            // Transform API response to standardized format with original line items for data merging
            $transformedResponse = $this->responseTransformer->transform($rawApiResponse, $apiRequestData['lineItems'] ?? []);
            
            // Apply additional processing options
            $transformedResponse = $this->applyResponseProcessing($transformedResponse, $requestData);
            
            $this->logger->info('GetAllRates - Response processed', [
                'successful' => $transformedResponse['isSuccessful'],
                'shipment_count' => count($transformedResponse['lineItemShipments'])
            ]);
            
            return $resultJson->setData([
                'success' => true,
                'data' => $transformedResponse
            ]);
            
        } catch (LocalizedException $e) {
            $this->logger->error('GetAllRates - LocalizedException: ' . $e->getMessage());
            
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('GetAllRates - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while processing your product/quote shipping request.')
            ]);
        }
    }

    /**
     * Apply additional response processing based on request parameters
     *
     * @param array $response
     * @param array $requestData
     * @return array
     */
    private function applyResponseProcessing(array $response, array $requestData): array
    {
        if (!isset($response['lineItemShipments']) || !is_array($response['lineItemShipments'])) {
            return $response;
        }

        $shipments = $response['lineItemShipments'];

        // Filter valid shipments if requested
        if (!empty($requestData['filter_valid_only'])) {
            $shipments = $this->responseTransformer->filterValidShipments($shipments);
        }

        // Sort by cost if requested
        if (!empty($requestData['sort_by_cost'])) {
            $shipments = $this->responseTransformer->sortByCost($shipments);
        }

        // Group by carrier if requested (for display optimization)
        if (!empty($requestData['group_by_carrier'])) {
            $response['groupedByCarrier'] = $this->responseTransformer->groupByCarrier($shipments);
        }

        $response['lineItemShipments'] = $shipments;

        return $response;
    }
}