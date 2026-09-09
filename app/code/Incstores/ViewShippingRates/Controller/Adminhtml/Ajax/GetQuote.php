<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Incstores\ViewShippingRates\Model\QuoteProvider;

class GetQuote extends Action implements HttpGetActionInterface
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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param QuoteProvider $quoteProvider
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteProvider $quoteProvider
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteProvider = $quoteProvider;
    }

    /**
     * Execute action to get quote data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $quoteNumber = $this->getRequest()->getParam('quote_number', '');
            $action = $this->getRequest()->getParam('action', 'get');
            
            if ($action === 'search') {
                // Search for quotes
                $searchTerm = $this->getRequest()->getParam('term', '');
                $quotes = $this->quoteProvider->searchQuotes($searchTerm, 20);
                
                return $resultJson->setData([
                    'success' => true,
                    'data' => $quotes
                ]);
                
            } else {
                // Get specific quote
                if (empty($quoteNumber)) {
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('Quote number is required')
                    ]);
                }
                
                $quoteData = $this->quoteProvider->getQuoteByNumber($quoteNumber);
                
                if (!$quoteData) {
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('Quote not found: %1', $quoteNumber)
                    ]);
                }
                
                return $resultJson->setData([
                    'success' => true,
                    'data' => $quoteData
                ]);
            }
            
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while retrieving quote data.')
            ]);
        }
    }
}