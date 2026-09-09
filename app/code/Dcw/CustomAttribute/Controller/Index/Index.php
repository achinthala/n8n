<?php

declare(strict_types=1);

namespace Dcw\CustomAttribute\Controller\Index;

use Dcw\CustomAttribute\ViewModel\Data as ViewModelData;
use Dcw\FlooringCalculation\Helper\Data as FlooringCalculationHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Class Index
 * @package Dcw\CustomAttribute\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ViewModelData $viewModelData,
        private readonly JsonFactory $resultJsonFactory,
        private readonly FlooringCalculationHelper $flooringCalculationHelper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData([
            'detailsHtml' => "",
            'shipToText' => ""
        ]);
        $productId = $this->getRequest()->getParam('productId');
        
        if ($productId) {
            $getDetailsAttributeHtml = $this->viewModelData->getProductDeatils($productId);
            $shipToText = "";

            try {
                $product = $this->productRepository->getById($productId);
                $shipToText = $this->flooringCalculationHelper->getExpectedShipDate($product);
            } catch (NoSuchEntityException $e) {
                $this->logger->info('An error occurred: ' . $e->getMessage());
            }
            
            $resultJson->setData([
                'detailsHtml' => $getDetailsAttributeHtml,
                'shipToText' => $shipToText
            ]);
        }

        return $resultJson;
    }
}

