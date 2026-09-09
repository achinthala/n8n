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
use Incstores\ViewShippingRates\Model\QuoteProvider;
use Incstores\ViewShippingRates\Model\Request\QuoteRequestTransformer;
use Incstores\ViewShippingRates\Model\Api\AllShippingRatesApi;
use Incstores\ViewShippingRates\Model\Response\AllResponseTransformer;
use Psr\Log\LoggerInterface;

class GetQuoteRates extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Incstores_ViewShippingRates::shipping_rates';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;


    /**
     * @var QuoteProvider
     */
    private $quoteProvider;

    /**
     * @var QuoteRequestTransformer
     */
    private $quoteRequestTransformer;

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
     * @param QuoteProvider $quoteProvider
     * @param QuoteRequestTransformer $quoteRequestTransformer
     * @param AllShippingRatesApi $allShippingRatesApi
     * @param AllResponseTransformer $allResponseTransformer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteProvider $quoteProvider,
        QuoteRequestTransformer $quoteRequestTransformer,
        AllShippingRatesApi $allShippingRatesApi,
        AllResponseTransformer $allResponseTransformer,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteProvider = $quoteProvider;
        $this->quoteRequestTransformer = $quoteRequestTransformer;
        $this->allShippingRatesApi = $allShippingRatesApi;
        $this->allResponseTransformer = $allResponseTransformer;
        $this->logger = $logger;
    }

    /**
     * Execute action to get shipping rates for a quote
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
            
            $this->logger->info('GetQuoteRates - Request received', [
                'quote_id' => $requestData['quote_id'] ?? 'missing',
                'transaction_id' => $requestData['transaction_id'] ?? 'missing'
            ]);

            // Validate required fields
            if (empty($requestData['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }

            // Get quote data
            $quoteData = $this->quoteProvider->getQuoteByNumber((string) $requestData['quote_id']);
            
            if (!$quoteData) {
                throw new LocalizedException(__('Quote not found: %1', $requestData['quote_id']));
            }

            // Log the retrieved quote data for debugging
            $this->logger->info('GetQuoteRates - Retrieved quote data', [
                'quote_data' => $quoteData
            ]);

            // Check if quote has shipping address or if user selected one
            if (!$quoteData['hasShippingAddress']) {
                // If no shipping address in quote, check if user provided address selection
                $selectedAddressId = $requestData['selected_address_id'] ?? null;
                
                if (empty($selectedAddressId)) {
                    // Return quote data with customer addresses for user selection
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('Please select a shipping address'),
                        'requiresAddressSelection' => true,
                        'quoteData' => $quoteData
                    ]);
                }
                
                // User selected an address, get the address details and update quote data
                if (!empty($quoteData['customerAddresses']['addresses'])) {
                    $selectedAddress = null;
                    foreach ($quoteData['customerAddresses']['addresses'] as $address) {
                        if ($address['id'] == $selectedAddressId) {
                            $selectedAddress = $address;
                            break;
                        }
                    }
                    
                    if (!$selectedAddress) {
                        throw new LocalizedException(__('Selected address not found'));
                    }
                    
                    // Update quote data with selected address
                    $quoteData['shippingAddress'] = [
                        'street' => $selectedAddress['street'],
                        'city' => $selectedAddress['city'],
                        'state' => $selectedAddress['region'],
                        'zipCode' => $selectedAddress['postcode'],
                        'country' => $selectedAddress['country_id']
                    ];
                    $quoteData['hasShippingAddress'] = true;
                    
                    $this->logger->info('GetQuoteRates - Using selected customer address', [
                        'address_id' => $selectedAddressId,
                        'postcode' => $selectedAddress['postcode']
                    ]);
                }
            }

            // Validate quote data (now should have shipping address)
            $validationErrors = $this->quoteRequestTransformer->validate($quoteData);
            if (!empty($validationErrors)) {
                $this->logger->error('GetQuoteRates - Validation failed', [
                    'validation_errors' => $validationErrors,
                    'quote_data' => $quoteData
                ]);
                throw new LocalizedException(__('Quote validation failed: %1', implode(', ', $validationErrors)));
            }

            // Transform quote data to API request format
            $apiRequestData = $this->quoteRequestTransformer->transform($quoteData, $requestData);
            
            $this->logger->info('GetQuoteRates - Quote Data Retrieved', [
                'full_quote_data' => $quoteData
            ]);
            
            $this->logger->info('GetQuoteRates - API Request Data', [
                'full_api_request' => $apiRequestData
            ]);

            // Call shipping rates API
            $apiResponse = $this->allShippingRatesApi->getAllRates($apiRequestData);
            
            $this->logger->info('GetQuoteRates - API Response', [
                'full_api_response' => $apiResponse
            ]);

            // Transform API response with original line items for data merging
            $transformedResponse = $this->allResponseTransformer->transform($apiResponse, $apiRequestData['lineItems'] ?? []);

            $this->logger->info('GetQuoteRates - Response processed', [
                'api_success' => $apiResponse['isSuccessful'] ?? false,
                'rates_count' => isset($transformedResponse['data']['rates']) ? count($transformedResponse['data']['rates']) : 0
            ]);

            // Add quote information to response
            $transformedResponse['quoteInfo'] = [
                'quoteId' => $quoteData['quoteId'],
                'reservedOrderId' => $quoteData['reservedOrderId'],
                'customerEmail' => $quoteData['customerEmail'],
                'isAmastyQuote' => $quoteData['isAmastyQuote'] ?? false,
                'itemCount' => $quoteData['itemCount']
            ];

            // Add debug information for browser console logging
            $transformedResponse['debugInfo'] = [
                'originalQuoteData' => $quoteData,
                'transformedApiRequest' => $apiRequestData,
                'validationErrors' => []
            ];

            return $resultJson->setData([
                'success' => true,
                'data' => $transformedResponse
            ]);
            
        } catch (LocalizedException $e) {
            $this->logger->error('GetQuoteRates - LocalizedException: ' . $e->getMessage());
            
            // If we have quote data but validation failed, include debug info
            $responseData = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            if (isset($quoteData)) {
                $responseData['debugInfo'] = [
                    'originalQuoteData' => $quoteData,
                    'validationErrors' => isset($validationErrors) ? $validationErrors : [],
                    'error' => $e->getMessage()
                ];
            }
            
            return $resultJson->setData($responseData);
            
        } catch (\Exception $e) {
            $this->logger->error('GetQuoteRates - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while processing your quote shipping request.')
            ]);
        }
    }
}