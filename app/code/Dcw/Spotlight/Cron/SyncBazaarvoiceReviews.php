<?php
/**
 * Sync Bazaarvoice Reviews Cron Job
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Cron;

use Dcw\Spotlight\Service\BazaarvoiceReviewParser;
use Dcw\Spotlight\Service\ReviewDataSaver;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;

/**
 * Class SyncBazaarvoiceReviews
 * 
 * Runs daily at 3:00 AM to sync Bazaarvoice reviews
 */
class SyncBazaarvoiceReviews
{
    /**
     * @var BazaarvoiceReviewParser
     */
    private $reviewParser;

    /**
     * @var ReviewDataSaver
     */
    private $dataSaver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var State
     */
    private $state;

    /**
     * Constructor
     *
     * @param BazaarvoiceReviewParser $reviewParser
     * @param ReviewDataSaver $dataSaver
     * @param LoggerInterface $logger
     * @param State $state
     */
    public function __construct(
        BazaarvoiceReviewParser $reviewParser,
        ReviewDataSaver $dataSaver,
        LoggerInterface $logger,
        State $state
    ) {
        $this->reviewParser = $reviewParser;
        $this->dataSaver = $dataSaver;
        $this->logger = $logger;
        $this->state = $state;
    }

    /**
     * Execute cron job
     *
     * Runs daily at 3:00 AM to sync Bazaarvoice reviews
     *
     * @return void
     */
    public function execute()
    {
        try {
            // Set area code if not already set
            try {
                $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
            } catch (\Exception $e) {
                // Area code already set, continue
            }

            $this->logger->info('[Bazaarvoice Cron] Starting daily review sync at 3:00 AM');

            $startTime = microtime(true);

            // Check if XML file exists
            if (!$this->reviewParser->xmlFileExists()) {
                $this->logger->error('[Bazaarvoice Cron] XML file not found at expected location.');
                $this->logger->warning('[Bazaarvoice Cron] Skipping sync - will retry tomorrow at 3:00 AM');
                return;
            }

            // Parse reviews
            $this->logger->info("[Bazaarvoice Cron] Parsing XML file");
            $groupedData = $this->reviewParser->parseReviews();

            $totalProducts = count($groupedData);
            $totalReviews = 0;
            foreach ($groupedData as $data) {
                $totalReviews += $data['total_reviews'];
            }

            $this->logger->info(
                sprintf(
                    '[Bazaarvoice Cron] Found %d products with %d approved reviews',
                    $totalProducts,
                    $totalReviews
                )
            );

            // Save to database
            $this->logger->info('[Bazaarvoice Cron] Saving data to database...');
            $stats = $this->dataSaver->saveData($groupedData);

            $executionTime = round(microtime(true) - $startTime, 2);

            // Log results
            $this->logger->info(
                sprintf(
                    '[Bazaarvoice Cron] Sync completed successfully in %s seconds. ' .
                    'Ratings created: %d, Ratings updated: %d, Reviews saved: %d, Errors: %d',
                    $executionTime,
                    $stats['ratings_saved'],
                    $stats['ratings_updated'],
                    $stats['reviews_saved'],
                    $stats['errors']
                )
            );

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[Bazaarvoice Cron] Error during sync: %s. Stack trace: %s',
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );
        }
    }
}

