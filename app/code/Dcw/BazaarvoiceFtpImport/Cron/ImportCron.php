<?php

/**
 * Bazaarvoice FTP Import Cron Job
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Cron;

use Dcw\BazaarvoiceFtpImport\Model\ImportService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class ImportCron
{
    const XML_PATH_AUTO_IMPORT = 'bazaarvoice_ftp/import_settings/auto_import';
    const XML_PATH_CRON_SCHEDULE = 'bazaarvoice_ftp/import_settings/cron_schedule';

    /**
     * @var ImportService
     */
    private $importService;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ImportCron constructor.
     *
     * @param ImportService $importService
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImportService $importService,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->importService = $importService;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('Bazaarvoice FTP Import cron job started');

        // Check if auto import is enabled
        $autoImport = (bool) $this->scopeConfig->getValue(
            self::XML_PATH_AUTO_IMPORT,
            ScopeInterface::SCOPE_STORE
        );


        if(!$autoImport) {
            $this->logger->info('Bazaarvoice FTP auto import is disabled');
            return;
        }

        try {
            $result = $this->importService->execute();

            if ($result['success']) {
                $this->logger->info(sprintf(
                    'Bazaarvoice FTP Import completed successfully. Processed: %d files, Downloaded: %d files, Extracted: %d files',
                    $result['files_processed'],
                    $result['files_downloaded'],
                    $result['files_extracted']
                ));

                if (!empty($result['errors'])) {
                    $this->logger->warning('Bazaarvoice FTP Import completed with errors: ' . implode(', ', $result['errors']));
                }

            } else {
                $this->logger->error('Bazaarvoice FTP Import failed: ' . implode(', ', $result['errors']));
            }

        } catch (\Exception $e) {
            $this->logger->error('Bazaarvoice FTP Import cron job failed: ' . $e->getMessage());
        }

        $this->logger->info('Bazaarvoice FTP Import cron job finished');
    }
}
