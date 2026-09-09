<?php

namespace Hyva\MagentoProductRecommendations\Controller\Product;

use Dcw\AdvanceSearch\ViewModel\Data;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Action;

class CalculatedPrice extends Action
{
    public function __construct(
        private readonly Context $context,
        private readonly Data $advanceSearchViewModel,
        private readonly JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $productId = $this->getRequest()->getParam('productId') ?? null;

        if (!$productId) {
            return $resultJson->setData([
                'success' => false,
                'priceData' => 'Product Id Not Given'
            ]);
        }

        try {
            // Check if multiple product IDs are provided (comma-separated)
            if (strpos($productId, ',') !== false) {
                $productIds = array_filter(array_map('trim', explode(',', $productId)));
                $priceDataArray = [];
                
                foreach ($productIds as $singleProductId) {
                    $priceData = $this->advanceSearchViewModel->getCalculatedPrice($singleProductId);
                    $priceDataArray[$singleProductId] = [
                        'success' => true,
                        'priceData' => $priceData
                    ];
                }
                
                return $resultJson->setData($priceDataArray);
            } else {
                // Single product ID (backward compatibility)
                $priceData = $this->advanceSearchViewModel->getCalculatedPrice($productId);

                return $resultJson->setData([
                    'success' => true,
                    'priceData' => $priceData
                ]);
            }
        } catch (Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'priceData' => $e->getMessage()
            ]);
        }
    }
}
