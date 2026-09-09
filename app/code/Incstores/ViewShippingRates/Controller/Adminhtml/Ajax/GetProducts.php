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
use Incstores\ViewShippingRates\Model\ProductProvider;

class GetProducts extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Incstores_ViewShippingRates::shipping_rates';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ProductProvider
     */
    private $productProvider;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductProvider $productProvider
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductProvider $productProvider
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productProvider = $productProvider;
    }

    /**
     * Execute action to get products
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $searchTerm = $this->getRequest()->getParam('term', '');
            $productId = $this->getRequest()->getParam('product_id', null);
            $action = $this->getRequest()->getParam('action', 'search');
            
            if ($action === 'get_skus' && $productId) {
                // Get SKUs for a specific product
                $skus = $this->productProvider->getChildProducts((int) $productId);
                
                return $resultJson->setData([
                    'success' => true,
                    'data' => $skus
                ]);
                
            } else {
                // Search for products
                $limit = (int) $this->getRequest()->getParam('limit', 200);  // Increased default limit
                $products = $this->productProvider->getConfigurableProducts($searchTerm, $limit);
                
                return $resultJson->setData([
                    'success' => true,
                    'data' => $products
                ]);
            }
            
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while retrieving products.')
            ]);
        }
    }
}