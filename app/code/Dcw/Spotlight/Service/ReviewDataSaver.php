<?php
/**
 * Review Data Saver Service
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Service;

use Dcw\Spotlight\Model\ProductRatingsFactory;
use Dcw\Spotlight\Model\TopReviewsFactory;
use Dcw\Spotlight\Model\ResourceModel\ProductRatings as ProductRatingsResource;
use Dcw\Spotlight\Model\ResourceModel\TopReviews as TopReviewsResource;
use Dcw\Spotlight\Model\ResourceModel\ProductRatings\CollectionFactory as RatingsCollectionFactory;
use Dcw\Spotlight\Model\ResourceModel\TopReviews\CollectionFactory as ReviewsCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Class ReviewDataSaver
 */
class ReviewDataSaver
{
    /**
     * @var ProductRatingsFactory
     */
    private $productRatingsFactory;

    /**
     * @var TopReviewsFactory
     */
    private $topReviewsFactory;

    /**
     * @var ProductRatingsResource
     */
    private $productRatingsResource;

    /**
     * @var TopReviewsResource
     */
    private $topReviewsResource;

    /**
     * @var RatingsCollectionFactory
     */
    private $ratingsCollectionFactory;

    /**
     * @var ReviewsCollectionFactory
     */
    private $reviewsCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ProductRatingsFactory $productRatingsFactory
     * @param TopReviewsFactory $topReviewsFactory
     * @param ProductRatingsResource $productRatingsResource
     * @param TopReviewsResource $topReviewsResource
     * @param RatingsCollectionFactory $ratingsCollectionFactory
     * @param ReviewsCollectionFactory $reviewsCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRatingsFactory $productRatingsFactory,
        TopReviewsFactory $topReviewsFactory,
        ProductRatingsResource $productRatingsResource,
        TopReviewsResource $topReviewsResource,
        RatingsCollectionFactory $ratingsCollectionFactory,
        ReviewsCollectionFactory $reviewsCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->productRatingsFactory = $productRatingsFactory;
        $this->topReviewsFactory = $topReviewsFactory;
        $this->productRatingsResource = $productRatingsResource;
        $this->topReviewsResource = $topReviewsResource;
        $this->ratingsCollectionFactory = $ratingsCollectionFactory;
        $this->reviewsCollectionFactory = $reviewsCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Save product ratings and top reviews
     *
     * @param array $groupedData
     * @return array
     */
    public function saveData(array $groupedData): array
    {
        $stats = [
            'ratings_saved' => 0,
            'ratings_updated' => 0,
            'reviews_saved' => 0,
            'errors' => 0
        ];

        foreach ($groupedData as $sku => $data) {
            try {
                // Convert SKU to string
                $skuString = (string)$sku;
                
                // Save product rating
                $ratingResult = $this->saveProductRating($skuString, $data);
                if ($ratingResult === 'created') {
                    $stats['ratings_saved']++;
                } elseif ($ratingResult === 'updated') {
                    $stats['ratings_updated']++;
                }

                // Save top reviews
                $reviewsCount = $this->saveTopReviews($skuString, $data['top_reviews']);
                $stats['reviews_saved'] += $reviewsCount;

            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    "Error saving data for SKU %s: %s",
                    $sku,
                    $e->getMessage()
                ));
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Save or update product rating
     *
     * @param string $sku
     * @param array $data
     * @return string
     * @throws \Exception
     */
    private function saveProductRating(string $sku, array $data): string
    {
        $collection = $this->ratingsCollectionFactory->create()
            ->addFieldToFilter('sku', $sku);

        if ($collection->getSize() > 0) {
            // Update existing
            $rating = $collection->getFirstItem();
            $rating->setAvgRatingValue($data['avg_rating']);
            $rating->setReviewsCount($data['total_reviews']);
            $this->productRatingsResource->save($rating);
            return 'updated';
        } else {
            // Create new
            $rating = $this->productRatingsFactory->create();
            $rating->setSku($sku);
            $rating->setAvgRatingValue($data['avg_rating']);
            $rating->setReviewsCount($data['total_reviews']);
            $rating->setBestRating(5);
            $this->productRatingsResource->save($rating);
            return 'created';
        }
    }

    /**
     * Save top reviews for a product
     *
     * @param string $sku
     * @param array $topReviews
     * @return int
     * @throws \Exception
     */
    private function saveTopReviews(string $sku, array $topReviews): int
    {
        // Delete existing reviews for this SKU
        $collection = $this->reviewsCollectionFactory->create()
            ->addFieldToFilter('sku', $sku);
        
        foreach ($collection as $review) {
            $this->topReviewsResource->delete($review);
        }

        // Save new top reviews
        $count = 0;
        foreach ($topReviews as $reviewData) {
            try {
                $review = $this->topReviewsFactory->create();
                $review->setSku($sku);
                $review->setReviewId($reviewData['review_id']);
                $review->setAuthorName($reviewData['author_name']);
                
                // Format date
                if (!empty($reviewData['date_published'])) {
                    $date = new \DateTime($reviewData['date_published']);
                    $review->setDatePublished($date->format('Y-m-d H:i:s'));
                }
                
                $review->setReviewTitle($reviewData['review_title']);
                $review->setReviewBody($reviewData['review_body']);
                $review->setRatingValue($reviewData['rating_value']);
                $review->setBestRating($reviewData['best_rating']);
                
                $this->topReviewsResource->save($review);
                $count++;
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    "Error saving review %s for SKU %s: %s",
                    $reviewData['review_id'],
                    $sku,
                    $e->getMessage()
                ));
            }
        }

        return $count;
    }

    /**
     * Clear all data
     *
     * @return void
     */
    public function clearAllData(): void
    {
        try {
            // Clear ratings
            $ratingsCollection = $this->ratingsCollectionFactory->create();
            foreach ($ratingsCollection as $rating) {
                $this->productRatingsResource->delete($rating);
            }

            // Clear reviews
            $reviewsCollection = $this->reviewsCollectionFactory->create();
            foreach ($reviewsCollection as $review) {
                $this->topReviewsResource->delete($review);
            }

            $this->logger->info("All review data cleared successfully");
        } catch (\Exception $e) {
            $this->logger->error("Error clearing data: " . $e->getMessage());
        }
    }
}

