<?php

declare(strict_types=1);

namespace Dcw\Spotlight\ViewModel;

use Amasty\SeoRichData\Model\JsonLd\ProductInfo;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

class Data implements ArgumentInterface
{
    /**
     * @var ProductInfo
     */
    private $seoRichDataProductInfo;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ProductInfo $seoRichDataProductInfo,
        LoggerInterface $logger
    ) {
        $this->seoRichDataProductInfo = $seoRichDataProductInfo;
        $this->logger = $logger;
    }

    public function getProductInfoJsonLd($product, $pageType = null)
    {
        try {
            if ($pageType) {
                $this->seoRichDataProductInfo->setPageType($pageType);
            }
            $resultArray = $this->seoRichDataProductInfo->extract($product);

            // Remove any empty or problematic values
            $resultArray = $this->cleanEmptyValues($resultArray);

            // Encode with proper flags to ensure valid JSON in HTML
            $json = json_encode(
                $resultArray,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            
            // Check for JSON encoding errors
            if ($json === false) {
                $error = json_last_error_msg();
                $this->logger->error('JSON encoding error for product ' . $product->getId() . ': ' . $error);
                $this->logger->error('Data that failed: ' . print_r($resultArray, true));
                return '';
            }
            
            // Validate JSON by decoding it back
            $validated = json_decode($json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('JSON validation error: ' . json_last_error_msg());
                return '';
            }
            
            $result = "<script type=\"application/ld+json\">{$json}</script>";

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error generating JSON-LD for product ' . $product->getId() . ': ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return '';
        }
    }

    /**
     * Recursively remove empty values from array
     *
     * @param array $data
     * @return array
     */
    private function cleanEmptyValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Don't remove aggregateRating or review even if empty (schema requirement)
                if ($key === 'aggregateRating' || $key === 'review') {
                    // Keep empty arrays for these fields
                    continue;
                }
                $data[$key] = $this->cleanEmptyValues($value);
                // Remove empty arrays except aggregateRating and review
                if (empty($data[$key])) {
                    unset($data[$key]);
                }
            } elseif ($value === null) {
                // Remove null values
                unset($data[$key]);
            } elseif ($value === '' && $key !== 'description') {
                // Remove empty strings except description
                unset($data[$key]);
            }
        }
        return $data;
    }
}
