<?php

declare(strict_types=1);

namespace Dcw\CacheWarmer\Plugin;

use Amasty\Fpc\Model\QueuePageRepository;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Plugin to preserve spotlight URLs when clearing the queue
 * Instead of clearing all URLs, it only removes non-spotlight URLs
 */
class QueuePageRepositoryPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Instead of clearing all, only delete non-spotlight URLs
     * This preserves spotlight URLs while removing everything else
     *
     * @param QueuePageRepository $subject
     * @param callable $proceed
     * @return void
     */
    public function aroundClear(QueuePageRepository $subject, callable $proceed): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('amasty_fpc_queue_page');
            
            // Count spotlight URLs before deletion
            $selectSpotlight = $connection->select()
                ->from($tableName, [new \Zend_Db_Expr('COUNT(*)')])
                ->where('url LIKE ?', '%spotlight%');
            
            $spotlightCount = (int)$connection->fetchOne($selectSpotlight);
            
            // Delete only URLs that DON'T contain "spotlight"
            $connection->delete(
                $tableName,
                ['url NOT LIKE ?' => '%spotlight%']
            );
            
            // Count remaining URLs (should be only spotlight URLs)
            $selectRemaining = $connection->select()
                ->from($tableName, [new \Zend_Db_Expr('COUNT(*)')]);
            
            $remainingCount = (int)$connection->fetchOne($selectRemaining);
            
            if ($spotlightCount > 0) {
                $this->logger->info(
                    "[CacheWarmer] Cleared queue while preserving {$spotlightCount} spotlight URLs. " .
                    "Remaining in queue: {$remainingCount}"
                );
            } else {
                // If no spotlight URLs, proceed with normal clear
                $proceed();
                $this->logger->info(
                    "[CacheWarmer] No spotlight URLs found, cleared entire queue"
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                '[CacheWarmer] Error clearing queue while preserving spotlight URLs: ' . $e->getMessage(),
                ['exception' => $e]
            );
            // Fallback to normal clear on error
            $proceed();
        }
    }
}
