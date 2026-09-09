<?php

declare(strict_types=1);

namespace Hyva\MagentoProductRecommendations\Controller\Product;

use Dcw\Custom\ViewModel\Data as CustomViewModel;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class ListHoverImage extends Action
{
    public function __construct(
        Context $context,
        private readonly CustomViewModel $customViewModel,
        private readonly JsonFactory $resultJsonFactory,
		private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $productId = $this->getRequest()->getParam('productId');
		
		$this->logger->critical(sprintf(
			'ProductId: %s, URI: %s',
			$productId,
			$this->getRequest()->getRequestUri()
		));

        if (!$productId) {
            return $resultJson->setData([
                'success' => false,
                'message' => 'Product Id Not Given',
            ]);
        }

        try {
            if (str_contains((string) $productId, ',')) {
                $productIds = array_filter(array_map('trim', explode(',', (string) $productId)));
                $hoverData = [];

                foreach ($productIds as $singleProductId) {
                    $hoverData[$singleProductId] = $this->customViewModel->getListHoverImageUrl((int) $singleProductId);
                }

                return $resultJson->setData([
                    'success' => true,
                    'hoverImages' => $hoverData,
                ]);
            }

            return $resultJson->setData([
                'success' => true,
                'hoverImages' => [
                    $productId => $this->customViewModel->getListHoverImageUrl((int) $productId),
                ],
            ]);
        } catch (Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
