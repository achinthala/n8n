<?php
declare(strict_types=1);

namespace Dcw\FlooringCalculation\Controller\Index;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CbcTiles extends Action
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ProductFactory $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger,
        private readonly SerializerInterface $serializer
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $productId = (int)$this->getRequest()->getParam('child_product_id');

        try {
            $mainProduct = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $exception) {
            $this->logger->error($exception->getTraceAsString(), [$productId]);

            $mainProduct = $this->productFactory->create();
        }

        $products = $this->productCollectionFactory->create();
        $products->addAttributeToSelect('*')
            ->addAttributeToFilter(
                'sku',
                [
                    'in' => [
                        $mainProduct->getSku() . '_corner', $mainProduct->getSku() .
                        '_border', $mainProduct->getSku() . '_center'
                    ]
                ]
            )->addAttributeToFilter('status', 1);

        $cbcItemsArr = [];

        foreach ($products as $product) {
            $cbcItemArr = [];
            $cbc_type = str_replace([$mainProduct->getSku(), '_', '-'], '', $product->getSku());
            $cbc_type = strtolower($cbc_type);
            $cbcItemArr['cbc_type'] = $cbc_type;
            $cbcItemArr['cbc_product_id'] = $product->getId();
            $cbcItemArr['cbc_product_name'] = $product->getName();
            $cbcItemArr['cbc_product_price'] = $product->getPrice();
            $cbcItemsArr[] = $cbcItemArr;
        }

        return $resultJson->setData($this->serializer->serialize($cbcItemsArr));
    }
}
