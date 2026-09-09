<?php

declare(strict_types=1);

namespace Dcw\CacheWarmer\Service;

use Amasty\Fpc\Model\QueuePageRepository;
use Amasty\Fpc\Api\Data\QueuePageInterface;
use Amasty\Fpc\Model\Config\Source\PageType;
use Amasty\Fpc\Model\Source\PageType\Factory as PageTypeFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Service to check if URLs are already pending in the cache warmer queue
 */
class QueueChecker
{
    /**
     * @var QueuePageRepository
     */
    private $queuePageRepository;

    /**
     * @var PageTypeFactory
     */
    private $pageTypeFactory;

    /**
     * @var UrlRewriteCollectionFactory
     */
    private $urlRewriteCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param QueuePageRepository $queuePageRepository
     * @param PageTypeFactory $pageTypeFactory
     * @param UrlRewriteCollectionFactory $urlRewriteCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        QueuePageRepository $queuePageRepository,
        PageTypeFactory $pageTypeFactory,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->queuePageRepository = $queuePageRepository;
        $this->pageTypeFactory = $pageTypeFactory;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Check if product URL is already pending in the queue
     *
     * @param int $productId
     * @param int|null $storeId
     * @return bool
     */
    public function isProductUrlPending(int $productId, ?int $storeId): bool
    {
        try {
            $urls = $this->getProductUrls($productId, $storeId);
            
            foreach ($urls as $url) {
                $queuePage = $this->queuePageRepository->getByUrl($url, $storeId);
                
                // If queue page exists (has ID), it's already in the queue (pending)
                if ($queuePage->getId()) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error checking product URL queue status',
                [
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]
            );
            // On error, assume not pending to allow queuing
            return false;
        }
    }

    /**
     * Check if category URL is already pending in the queue
     *
     * @param int $categoryId
     * @param int|null $storeId
     * @return bool
     */
    public function isCategoryUrlPending(int $categoryId, ?int $storeId): bool
    {
        try {
            $urls = $this->getCategoryUrls($categoryId, $storeId);
            
            foreach ($urls as $url) {
                $queuePage = $this->queuePageRepository->getByUrl($url, $storeId);
                
                // If queue page exists (has ID), it's already in the queue (pending)
                if ($queuePage->getId()) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error checking category URL queue status',
                [
                    'category_id' => $categoryId,
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]
            );
            // On error, assume not pending to allow queuing
            return false;
        }
    }

    /**
     * Get product URLs for a product
     *
     * @param int $productId
     * @param int|null $storeId
     * @return array
     */
    private function getProductUrls(int $productId, ?int $storeId): array
    {
        try {
            $filter = function (UrlRewriteCollection $collection) use ($productId, $storeId) {
                $collection->addFieldToFilter('product_entity.entity_id', $productId);
                if ($storeId !== null) {
                    $collection->addFieldToFilter('store_id', $storeId);
                }
            };

            $pageType = $this->pageTypeFactory->create(PageType::TYPE_PRODUCT, [
                'filterCollection' => $filter
            ]);

            $pages = $pageType->getAllPages();
            $urls = [];

            foreach ($pages as $page) {
                if (isset($page['url'])) {
                    $urls[] = $page['url'];
                }
            }

            return $urls;
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error getting product URLs',
                [
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]
            );
            return [];
        }
    }

    /**
     * Check if spotlight URL is already pending in the queue
     *
     * @param string $url
     * @param int|null $storeId
     * @return bool
     */
    public function isSpotlightUrlPending(string $url, ?int $storeId): bool
    {
        try {
            $queuePage = $this->queuePageRepository->getByUrl($url, $storeId);
            
            // If queue page exists (has ID), it's already in the queue (pending)
            if ($queuePage->getId()) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error checking spotlight URL queue status',
                [
                    'url' => $url,
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]
            );
            // On error, assume not pending to allow queuing
            return false;
        }
    }

    /**
     * Get category URLs for a category
     *
     * @param int $categoryId
     * @param int|null $storeId
     * @return array
     */
    private function getCategoryUrls(int $categoryId, ?int $storeId): array
    {
        try {
            $filter = function (UrlRewriteCollection $collection) use ($categoryId, $storeId) {
                $collection->addFieldToFilter('entity_id', $categoryId);
                if ($storeId !== null) {
                    $collection->addFieldToFilter('store_id', $storeId);
                }
            };

            $pageType = $this->pageTypeFactory->create(PageType::TYPE_CATEGORY, [
                'filterCollection' => $filter
            ]);

            $pages = $pageType->getAllPages();
            $urls = [];

            foreach ($pages as $page) {
                if (isset($page['url'])) {
                    $urls[] = $page['url'];
                }
            }

            return $urls;
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error getting category URLs',
                [
                    'category_id' => $categoryId,
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]
            );
            return [];
        }
    }
}

