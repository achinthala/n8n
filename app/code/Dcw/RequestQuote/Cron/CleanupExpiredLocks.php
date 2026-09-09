<?php
declare(strict_types=1);

namespace Dcw\RequestQuote\Cron;

use Dcw\RequestQuote\Service\QuoteLockService;
use Psr\Log\LoggerInterface;

/**
 * Cron job to clean up expired quote locks
 */
class CleanupExpiredLocks
{
    /**
     * @param QuoteLockService $quoteLockService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly QuoteLockService $quoteLockService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute cron job to clean up expired locks
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $deleted = $this->quoteLockService->cleanupExpiredLocks();
            if ($deleted > 0) {
                $this->logger->info("Cleaned up {$deleted} expired quote lock(s)");
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in cleanup expired locks cron: ' . $e->getMessage());
        }
    }
}

