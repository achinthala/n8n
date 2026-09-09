<?php

/**
 * GMC Convert Cron Job
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Cron;

use Dcw\BazaarvoiceFtpImport\Model\GmcReviewConverter;
use Dcw\BazaarvoiceFtpImport\Model\FileProcessor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class GmcConvertCron
{
    const XML_PATH_GMC_ENABLED = 'bazaarvoice_ftp/gmc_settings/enabled';
    const XML_PATH_GMC_CRON_SCHEDULE = 'bazaarvoice_ftp/gmc_settings/cron_schedule';

    /**
     * @var GmcReviewConverter
     */
    private $gmcConverter;

    /**
     * @var FileProcessor
     */
    private $fileProcessor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GmcConvertCron constructor.
     *
     * @param GmcReviewConverter $gmcConverter
     * @param FileProcessor $fileProcessor
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        GmcReviewConverter $gmcConverter,
        FileProcessor $fileProcessor,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->gmcConverter = $gmcConverter;
        $this->fileProcessor = $fileProcessor;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Execute GMC conversion cron job
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $this->logger->info('GMC Convert Cron: Starting execution');

            // Check if GMC conversion is enabled
            if (!$this->gmcConverter->isEnabled()) {
                $this->logger->info('GMC Convert Cron: GMC conversion is disabled, skipping');
                return;
            }

            // Get import directory
            $importPath = $this->fileProcessor->getAbsoluteLocalPath();
            $extractPath = $importPath . '/extracted/';

            $filesProcessed = 0;
            $gmcFilesGenerated = 0;
            $errors = [];

            // Process XML files in the extracted directory
            if (!is_dir($extractPath)) {
                $this->logger->info('GMC Convert Cron: Extracted directory not found, skipping');
                return;
            }

            $xmlFiles = glob($extractPath . '*.xml');

            if (empty($xmlFiles)) {
                $this->logger->info('GMC Convert Cron: No XML files found, skipping');
                return;
            }

            $this->logger->info('GMC Convert Cron: Found ' . count($xmlFiles) . ' XML file(s) to process');

            foreach ($xmlFiles as $xmlFile) {
                try {
                    $this->logger->info('GMC Convert Cron: Converting ' . basename($xmlFile));

                    $gmcFile = $this->gmcConverter->convertToGmc($xmlFile);

                    if ($gmcFile) {
                        $gmcFilesGenerated++;
                        $this->logger->info('GMC Convert Cron: Generated ' . basename($gmcFile));
                    }

                    $filesProcessed++;

                } catch (\Exception $e) {
                    $error = 'Failed to convert ' . basename($xmlFile) . ': ' . $e->getMessage();
                    $this->logger->error('GMC Convert Cron error: ' . $error);
                    $errors[] = $error;
                }

            }

            $this->logger->info("GMC Convert Cron: Completed. Files processed: {$filesProcessed}, GMC files generated: {$gmcFilesGenerated}");

            if (!empty($errors)) {
                $this->logger->error('GMC Convert Cron: Errors encountered: ' . implode(', ', $errors));
            }

        } catch (\Exception $e) {
            $this->logger->error('GMC Convert Cron failed: ' . $e->getMessage());
        }

    }
}
