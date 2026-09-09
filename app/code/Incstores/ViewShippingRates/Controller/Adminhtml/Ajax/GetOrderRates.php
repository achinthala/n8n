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
use Incstores\ViewShippingRates\Model\OrderProvider;
use Incstores\ViewShippingRates\Model\Request\OrderRequestTransformer;
use Incstores\ViewShippingRates\Model\Api\AllShippingRatesApi;
use Incstores\ViewShippingRates\Model\Response\AllResponseTransformer;
use Psr\Log\LoggerInterface;

class GetOrderRates extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Incstores_ViewShippingRates::shipping_rates';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var OrderProvider
     */
    private $orderProvider;

    /**
     * @var OrderRequestTransformer
     */
    private $orderRequestTransformer;

    /**
     * @var AllShippingRatesApi
     */
    private $allShippingRatesApi;

    /**
     * @var AllResponseTransformer
     */
    private $allResponseTransformer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param OrderProvider $orderProvider
     * @param OrderRequestTransformer $orderRequestTransformer
     * @param AllShippingRatesApi $allShippingRatesApi
     * @param AllResponseTransformer $allResponseTransformer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderProvider $orderProvider,
        OrderRequestTransformer $orderRequestTransformer,
        AllShippingRatesApi $allShippingRatesApi,
        AllResponseTransformer $allResponseTransformer,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderProvider = $orderProvider;
        $this->orderRequestTransformer = $orderRequestTransformer;
        $this->allShippingRatesApi = $allShippingRatesApi;
        $this->allResponseTransformer = $allResponseTransformer;
        $this->logger = $logger;
    }

    /**
     * Execute action to get shipping rates for an order
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            // Get request data using standard Magento pattern
            $requestData = $this->getRequest()->getPostValue();
            
            if (empty($requestData)) {
                throw new LocalizedException(__('Invalid request data'));
            }
            
            $this->logger->info('GetOrderRates - Request received', [
                'order_id' => $requestData['order_id'] ?? 'missing',
                'transaction_id' => $requestData['transaction_id'] ?? 'missing'
            ]);

            // Validate required fields
            if (empty($requestData['order_id'])) {
                throw new LocalizedException(__('Order ID is required'));
            }

            // Get order data
            $orderData = $this->orderProvider->getOrderByNumber((string) $requestData['order_id']);
            
            if (!$orderData) {
                throw new LocalizedException(__('Order not found: %1', $requestData['order_id']));
            }

            // Log the retrieved order data for debugging
            $this->logger->info('GetOrderRates - Retrieved order data', [
                'order_data' => $orderData
            ]);

            // Check if order has shipping address or if user selected one
            if (!$orderData['hasShippingAddress']) {
                // If no shipping address in order, check if user provided address selection
                $selectedAddressId = $requestData['selected_address_id'] ?? null;
                
                if (empty($selectedAddressId)) {
                    // Return order data with customer addresses for user selection
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('Please select a shipping address'),
                        'requiresAddressSelection' => true,
                        'orderData' => $orderData
                    ]);
                }
                
                // User selected an address, get the address details and update order data
                if (!empty($orderData['customerAddresses']['addresses'])) {
                    $selectedAddress = null;
                    foreach ($orderData['customerAddresses']['addresses'] as $address) {
                        if ($address['id'] == $selectedAddressId) {
                            $selectedAddress = $address;
                            break;
                        }
                    }
                    
                    if (!$selectedAddress) {
                        throw new LocalizedException(__('Selected address not found'));
                    }
                    
                    // Update order data with selected address
                    $orderData['shippingAddress'] = [
                        'street' => $selectedAddress['street'],
                        'city' => $selectedAddress['city'],
                        'state' => $selectedAddress['region'],
                        'zipCode' => $selectedAddress['postcode'],
                        'country' => $selectedAddress['country_id']
                    ];
                    $orderData['hasShippingAddress'] = true;
                    
                    $this->logger->info('GetOrderRates - Using selected customer address', [
                        'address_id' => $selectedAddressId,
                        'postcode' => $selectedAddress['postcode']
                    ]);
                }
            }

            // Validate order data (now should have shipping address)
            $validationErrors = $this->orderRequestTransformer->validate($orderData);
            if (!empty($validationErrors)) {
                $this->logger->error('GetOrderRates - Validation failed', [
                    'validation_errors' => $validationErrors,
                    'order_data' => $orderData
                ]);
                throw new LocalizedException(__('Order validation failed: %1', implode(', ', $validationErrors)));
            }

            // Transform order data to API request format
            $apiRequestData = $this->orderRequestTransformer->transform($orderData, $requestData);
            
            $this->logger->info('GetOrderRates - Order Data Retrieved', [
                'full_order_data' => $orderData
            ]);
            
            $this->logger->info('GetOrderRates - API Request Data', [
                'full_api_request' => $apiRequestData
            ]);

            // Call shipping rates API
            $apiResponse = $this->allShippingRatesApi->getAllRates($apiRequestData);
            
            $this->logger->info('GetOrderRates - API Response', [
                'full_api_response' => $apiResponse
            ]);

            // Transform API response with original line items for data merging
            $transformedResponse = $this->allResponseTransformer->transform($apiResponse, $apiRequestData['lineItems'] ?? []);

            $this->logger->info('GetOrderRates - Response processed', [
                'api_success' => $apiResponse['isSuccessful'] ?? false,
                'rates_count' => isset($transformedResponse['data']['rates']) ? count($transformedResponse['data']['rates']) : 0
            ]);

            // Add order information to response
            $transformedResponse['orderInfo'] = [
                'orderId' => $orderData['orderId'],
                'incrementId' => $orderData['incrementId'],
                'customerEmail' => $orderData['customerEmail'],
                'isOrder' => $orderData['isOrder'] ?? true,
                'status' => $orderData['status'],
                'state' => $orderData['state'],
                'itemCount' => $orderData['itemCount']
            ];

            // Add debug information for browser console logging
            $transformedResponse['debugInfo'] = [
                'originalOrderData' => $orderData,
                'transformedApiRequest' => $apiRequestData,
                'validationErrors' => []
            ];

            return $resultJson->setData([
                'success' => true,
                'data' => $transformedResponse
            ]);
            
        } catch (LocalizedException $e) {
            $this->logger->error('GetOrderRates - LocalizedException: ' . $e->getMessage());
            
            // If we have order data but validation failed, include debug info
            $responseData = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            if (isset($orderData)) {
                $responseData['debugInfo'] = [
                    'originalOrderData' => $orderData,
                    'validationErrors' => isset($validationErrors) ? $validationErrors : [],
                    'error' => $e->getMessage()
                ];
            }
            
            return $resultJson->setData($responseData);
            
        } catch (\Exception $e) {
            $this->logger->error('GetOrderRates - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while processing your order shipping request.')
            ]);
        }
    }
}