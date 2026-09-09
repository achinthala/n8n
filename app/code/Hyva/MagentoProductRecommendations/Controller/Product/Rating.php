<?php

namespace Hyva\MagentoProductRecommendations\Controller\Product;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;

class Rating implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ReviewCollectionFactory
     */
    private $reviewCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductRepository $productRepository
     * @param ReviewCollectionFactory $reviewCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepository $productRepository,
        ReviewCollectionFactory $reviewCollectionFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
    }

    /**
     * Execute method to fetch rating details
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $requestData = json_decode($this->getRequest()->getContent(), true);
        $productId = $requestData['productId'] ?? null;

        if (!$productId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Product ID is required.'),
            ]);
        }

        try {
            // Load product to verify it exists
            $product = $this->productRepository->getById($productId);

            // Fetch reviews for the product
            $reviews = $this->reviewCollectionFactory->create()
                ->addEntityFilter('product', $productId)
                ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED);

            $totalRating = 0;
            $reviewCount = 0;

            foreach ($reviews as $review) {
                $rating = $review->getRatingSummary(); // Assuming a method to get rating
                if ($rating) {
                    $totalRating += $rating;
                    $reviewCount++;
                }
            }

            $averageRating = $reviewCount ? round($totalRating / $reviewCount, 2) : 0;

            return $resultJson->setData([
                'success' => true,
                'averageRating' => $averageRating,
                'ratingCount' => $reviewCount,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
