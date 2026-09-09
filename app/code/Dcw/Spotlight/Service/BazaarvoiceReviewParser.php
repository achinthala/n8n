<?php
/**
 * Bazaarvoice Review Parser Service
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Service;

use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Class BazaarvoiceReviewParser
 */
class BazaarvoiceReviewParser
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * XML file path
     */
    const XML_FILE_PATH = 'pub/media/bazaarvoice/extracted/bv_incstores_standard_client_feed.xml';

    /**
     * Constructor
     *
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    /**
     * Parse XML file and extract product reviews
     *
     * @return array
     * @throws \Exception
     */
    public function parseReviews(): array
    {
        $filePath = $this->findXmlFile();

        if (!$filePath) {
            $message = $this->getFileNotFoundMessage();
            $this->logger->error($message);
            throw new \Exception($message);
        }

        $this->logger->info("Starting XML parsing from: {$filePath}");

        // Use XMLReader for memory-efficient parsing of large files
        $reader = new \XMLReader();
        $reader->open($filePath);

        $productsData = [];
        $currentProduct = null;
        $currentElement = '';

        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'Product') {
                $productId = $reader->getAttribute('id');
                
                // Parse product data
                $productXml = $reader->readOuterXml();
                $productData = $this->parseProductXml($productXml);
                
                if ($productData && !empty($productData['reviews'])) {
                    $productsData[$productId] = $productData;
                }
            }
        }

        $reader->close();

        $this->logger->info(sprintf("Parsed %d products with reviews", count($productsData)));

        return $this->groupProductsByParent($productsData);
    }

    /**
     * Parse individual product XML
     *
     * @param string $productXml
     * @return array|null
     */
    private function parseProductXml(string $productXml): ?array
    {
        try {
            $xml = new \SimpleXMLElement($productXml);
            
            $productId = (string)$xml['id'];
            $productName = isset($xml->Name) ? (string)$xml->Name : '';
            
            // Extract approved reviews only
            $reviews = [];
            if (isset($xml->Reviews->Review)) {
                foreach ($xml->Reviews->Review as $review) {
                    $moderationStatus = (string)$review->ModerationStatus;
                    
                    // Only include APPROVED reviews
                    if ($moderationStatus !== 'APPROVED') {
                        continue;
                    }

                    $reviewData = $this->extractReviewData($review);
                    if ($reviewData) {
                        $reviews[] = $reviewData;
                    }
                }
            }

            if (empty($reviews)) {
                return null;
            }

            return [
                'product_id' => $productId,
                'product_name' => $productName,
                'reviews' => $reviews
            ];
        } catch (\Exception $e) {
            $this->logger->error("Error parsing product XML: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract review data from XML element
     *
     * @param \SimpleXMLElement $review
     * @return array|null
     */
    private function extractReviewData(\SimpleXMLElement $review): ?array
    {
        try {
            $reviewId = (string)$review['id'];
            
            // Extract author name
            $authorName = 'Anonymous';
            if (isset($review->UserProfileReference->DisplayName)) {
                $authorName = (string)$review->UserProfileReference->DisplayName;
            } elseif (isset($review->ReviewerNickname)) {
                $authorName = (string)$review->ReviewerNickname;
            }

            // Check if anonymous
            if (isset($review->UserProfileReference->Anonymous)) {
                $isAnonymous = ((string)$review->UserProfileReference->Anonymous === 'true');
                if ($isAnonymous) {
                    $authorName = 'Anonymous';
                }
            }

            // Extract date
            $datePublished = isset($review->LastPublishTime) ? 
                (string)$review->LastPublishTime : 
                (isset($review->SubmissionTime) ? (string)$review->SubmissionTime : null);

            // Extract rating
            $rating = isset($review->Rating) ? (int)$review->Rating : null;
            $ratingRange = isset($review->RatingRange) ? (int)$review->RatingRange : 5;

            // Extract title and body
            $title = isset($review->Title) ? (string)$review->Title : '';
            $body = isset($review->ReviewText) ? (string)$review->ReviewText : '';

            // Check if it's ratings only
            $isRatingsOnly = isset($review->RatingsOnly) && 
                (string)$review->RatingsOnly === 'true';

            if ($rating === null) {
                return null;
            }

            return [
                'review_id' => $reviewId,
                'author_name' => $authorName,
                'date_published' => $datePublished,
                'review_title' => $title,
                'review_body' => $body,
                'rating_value' => $rating,
                'best_rating' => $ratingRange,
                'is_ratings_only' => $isRatingsOnly
            ];
        } catch (\Exception $e) {
            $this->logger->error("Error extracting review data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Group child products with parent
     *
     * @param array $productsData
     * @return array
     */
    private function groupProductsByParent(array $productsData): array
    {
        $groupedData = [];

        foreach ($productsData as $productId => $productData) {
            // Convert product ID to string for string operations
            $productIdStr = (string)$productId;
            
            // Check if this is a child product (contains underscore)
            if (strpos($productIdStr, '_') !== false) {
                // Extract parent SKU (everything before first underscore)
                $parentSku = explode('_', $productIdStr)[0];
            } else {
                $parentSku = $productIdStr;
            }

            // Initialize parent if not exists
            if (!isset($groupedData[$parentSku])) {
                $groupedData[$parentSku] = [
                    'sku' => $parentSku,
                    'product_name' => $productData['product_name'],
                    'reviews' => []
                ];
            }

            // Add reviews to parent
            $groupedData[$parentSku]['reviews'] = array_merge(
                $groupedData[$parentSku]['reviews'],
                $productData['reviews']
            );
        }

        // Calculate aggregated data for each parent
        foreach ($groupedData as $sku => &$data) {
            $data['total_reviews'] = count($data['reviews']);
            
            // Calculate average rating
            $totalRating = 0;
            foreach ($data['reviews'] as $review) {
                $totalRating += $review['rating_value'];
            }
            
            $data['avg_rating'] = $data['total_reviews'] > 0 ? 
                round($totalRating / $data['total_reviews'], 2) : 0;
            
            // Get top 5 reviews (sorted by rating, then by date)
            $reviews = $data['reviews'];
            usort($reviews, function ($a, $b) {
                // First sort by rating (descending)
                if ($b['rating_value'] != $a['rating_value']) {
                    return $b['rating_value'] - $a['rating_value'];
                }
                // Then by date (most recent first)
                return strtotime($b['date_published']) - strtotime($a['date_published']);
            });
            
            $data['top_reviews'] = array_slice($reviews, 0, 5);
        }

        return $groupedData;
    }

    /**
     * Find XML file
     *
     * @return string|null
     */
    private function findXmlFile(): ?string
    {
        $rootPath = $this->directoryList->getRoot();
        $fullPath = $rootPath . '/' . self::XML_FILE_PATH;
        
        if (file_exists($fullPath) && is_readable($fullPath)) {
            $this->logger->info("Found XML file at: {$fullPath}");
            return $fullPath;
        }
        
        return null;
    }

    /**
     * Get detailed error message when file not found
     *
     * @return string
     */
    private function getFileNotFoundMessage(): string
    {
        $rootPath = $this->directoryList->getRoot();
        $fullPath = $rootPath . '/' . self::XML_FILE_PATH;
        
        $message = "Bazaarvoice XML file not found!\n\n";
        $message .= "Expected location: {$fullPath}\n\n";
        $message .= "Possible solutions:\n";
        $message .= "- Check if Bazaarvoice feed export is running\n";
        $message .= "- Verify file permissions (should be readable)\n";
        $message .= "- Manually place the XML file at the expected location\n";
        $message .= "- Contact Bazaarvoice support if feed is not generating\n";
        
        return $message;
    }

    /**
     * Get file path (for backward compatibility)
     *
     * @return string
     */
    public function getFilePath(): string
    {
        $filePath = $this->findXmlFile();
        if ($filePath) {
            return $filePath;
        }
        
        // Return expected path even if not found (for error messages)
        return $this->directoryList->getRoot() . '/' . self::XML_FILE_PATH;
    }

    /**
     * Check if XML file exists
     *
     * @return bool
     */
    public function xmlFileExists(): bool
    {
        return $this->findXmlFile() !== null;
    }
}

