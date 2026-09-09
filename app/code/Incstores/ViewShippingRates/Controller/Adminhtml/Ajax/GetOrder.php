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
use Incstores\ViewShippingRates\Model\OrderProvider;

class GetOrder extends Action implements HttpGetActionInterface
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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param OrderProvider $orderProvider
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderProvider $orderProvider
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderProvider = $orderProvider;
    }

    /**
     * Execute action to get order data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $orderNumber = $this->getRequest()->getParam('order_number', '');
            $action = $this->getRequest()->getParam('action', 'get');
            
            if ($action === 'search') {
                // Search for orders
                $searchTerm = $this->getRequest()->getParam('term', '');
                $orders = $this->orderProvider->searchOrders($searchTerm, 20);
                
                return $resultJson->setData([
                    'success' => true,
                    'data' => $orders
                ]);
                
            } else {
                // Get specific order
                if (empty($orderNumber)) {
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('Order number is required')
                    ]);
                }
                
                $orderData = $this->orderProvider->getOrderByNumber($orderNumber);
                
                if (!$orderData) {
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('Order not found: %1', $orderNumber)
                    ]);
                }
                
                return $resultJson->setData([
                    'success' => true,
                    'data' => $orderData
                ]);
            }
            
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while retrieving order data.')
            ]);
        }
    }
}