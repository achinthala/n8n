<?php

declare(strict_types=1);

namespace Dcw\CacheWarmer\Cron;

use Dcw\CacheWarmer\Service\CacheWarmerService;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cron job to generate warmerurls.txt file with all active configurable product URLs
 */
class GenerateWarmerUrls
{
    /**
     * @var CacheWarmerService
     */
    private $cacheWarmerService;

    /**
     * @var State
     */
    private $state;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CacheWarmerService $cacheWarmerService
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CacheWarmerService $cacheWarmerService,
        State $state,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->cacheWarmerService = $cacheWarmerService;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('[CacheWarmer] Starting warmerurls.txt generation cron job');

        try {
            // Set area code for cron execution
            try {
                $this->state->setAreaCode(Area::AREA_FRONTEND);
            } catch (\Exception $e) {
                // Area already set, continue
            }

            // Generate URLs for default store (or you can loop through all stores if needed)
            $store = $this->storeManager->getStore();
            $storeId = (int)$store->getId();

            $this->logger->info("[CacheWarmer] Generating warmerurls.txt for store: {$store->getName()} (ID: {$storeId})");

            $result = $this->cacheWarmerService->generateWarmerUrlsFile($storeId);

            $this->logger->info(
                "[CacheWarmer] warmerurls.txt generated successfully. " .
                "Total URLs: {$result['count']}, File: {$result['file_path']}"
            );

        } catch (\Exception $e) {
            $this->logger->error(
                '[CacheWarmer] Error generating warmerurls.txt in cron: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
