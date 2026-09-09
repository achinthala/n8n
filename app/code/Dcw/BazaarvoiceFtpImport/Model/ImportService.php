<?php

/**
 * Bazaarvoice Import Service
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class ImportService
{
    /**
     * @var FtpService
     */
    private $ftpService;

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
     * @var GmcReviewConverter
     */
    private $gmcConverter;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * ImportService constructor.
     *
     * @param FtpService $ftpService
     * @param FileProcessor $fileProcessor
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param GmcReviewConverter $gmcConverter
     * @param File $fileDriver
     */
    public function __construct(
        FtpService $ftpService,
        FileProcessor $fileProcessor,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        GmcReviewConverter $gmcConverter,
        File $fileDriver
    ) {
        $this->ftpService = $ftpService;
        $this->fileProcessor = $fileProcessor;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->gmcConverter = $gmcConverter;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Execute the import process
     *
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    public function execute($storeId = null): array
    {
        $result = [
            'success' => false,
            'files_processed' => 0,
            'files_downloaded' => 0,
            'files_extracted' => 0,
            'errors' => [],
            'processed_files' => [],
            'message' => ''
        ];

        try {
            $this->logger->info('Starting Bazaarvoice FTP import process');

            // Check if import is enabled
            if (!$this->ftpService->isEnabled($storeId)) {
                throw new LocalizedException(__('Bazaarvoice FTP import is not enabled.'));
            }

            // Create local directory first
            try {
                $this->fileProcessor->createLocalDirectory($storeId);
                $this->logger->info('Directory creation successful, proceeding with FTP operations');
            } catch (\Exception $e) {
                $this->logger->error('Failed to create local directory: ' . $e->getMessage());
                $result['success'] = false;
                $result['message'] = 'Failed to create local directory: ' . $e->getMessage();
                return $result;
            }

            // Connect to FTP
            $this->ftpService->connect($storeId);

            try {
                // Get list of files to download
                $files = $this->ftpService->getFileList($storeId);

                if (empty($files)) {
                    $this->logger->info('No files found matching the pattern');
                    $result['success'] = true;
                    return $result;
                }

                $this->logger->info('Found ' . count($files) . ' files to process');

                // Process each file
                foreach ($files as $file) {
                    try {
                        $fileResult = $this->processFile($file, $storeId);
                        $result['processed_files'][] = $fileResult;
                        $result['files_processed']++;

                        if ($fileResult['downloaded']) {
                            $result['files_downloaded']++;
                        }

                        if ($fileResult['extracted']) {
                            $result['files_extracted']++;
                        }

                        if (!empty($fileResult['errors'])) {
                            $result['errors'] = array_merge($result['errors'], $fileResult['errors']);
                        }

                    } catch (\Exception $e) {
                        $error = 'Failed to process file ' . $file . ': ' . $e->getMessage();
                        $this->logger->error($error);
                        $result['errors'][] = $error;
                    }

                }

                $result['success'] = true;
                $this->logger->info('Bazaarvoice FTP import completed successfully');

            } finally {
                // Always disconnect from FTP
                $this->ftpService->disconnect();
            }

        } catch (\Exception $e) {
            $error = 'Bazaarvoice FTP import failed: ' . $e->getMessage();
            $this->logger->error($error);
            $result['errors'][] = $error;
        }

        return $result;
    }

    /**
     * Process a single file
     *
     * @param string $filename
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    private function processFile(string $filename, $storeId = null): array
    {
        $result = [
            'filename' => $filename,
            'downloaded' => false,
            'extracted' => false,
            'errors' => []
        ];

        $this->logger->info('Processing file: ' . $filename);

        try {
            // Get file size for logging
            $fileSize = $this->ftpService->getFileSize($filename, $storeId);
            $this->logger->info('File size: ' . $this->formatBytes($fileSize));

            // Download file
            $localPath = $this->fileProcessor->getAbsoluteLocalPath($storeId);
            $localFile = $localPath . '/' . $filename;

            $this->ftpService->downloadFile($filename, $localFile, $storeId);
            $result['downloaded'] = true;

            $this->logger->info('Downloaded file: ' . $filename);

            // Check if it's a compressed file and process it
            if ($this->fileProcessor->isZipFile($filename)) {
                $extractedFiles = $this->fileProcessor->extractZipFile($filename, $storeId);
                $result['extracted'] = true;
                $result['extracted_files'] = $extractedFiles;

                $this->logger->info('Extracted ' . count($extractedFiles) . ' files from: ' . $filename);

                // Remove zip file if configured to do so
                $this->fileProcessor->removeCompressedFile($filename, $storeId);
            } elseif ($this->fileProcessor->isGzipFile($filename)) {
                $decompressedFile = $this->fileProcessor->decompressGzipFile($filename, $storeId);
                $result['extracted'] = true;
                $result['extracted_files'] = [$decompressedFile];

                $this->logger->info('Decompressed gzip file: ' . $filename . ' to ' . $decompressedFile);

                // Remove gzip file if configured to do so
                $this->fileProcessor->removeCompressedFile($filename, $storeId);
            } else {
                $this->logger->info('File is not a compressed file, skipping extraction: ' . $filename);
            }

            // Convert to GMC format if enabled and we have an XML file
            $xmlFileToConvert = null;
            if ($this->fileProcessor->getFileExtension($filename) === 'xml') {
                $xmlFileToConvert = $localFile;
            } elseif ($this->fileProcessor->isGzipFile($filename) && isset($result['extracted_files'][0])) {
                // Use the decompressed XML file
                $extractPath = $this->fileProcessor->getAbsoluteLocalPath($storeId) . '/extracted/';
                $xmlFileToConvert = $extractPath . $result['extracted_files'][0];
            }

            if ($xmlFileToConvert && $this->fileDriver->isExists($xmlFileToConvert)) {
                try {
                    $gmcFile = $this->gmcConverter->convertToGmc($xmlFileToConvert, $storeId);
                    if ($gmcFile) {
                        $result['gmc_file'] = $gmcFile;
                        $this->logger->info('Generated GMC file: ' . $gmcFile);
                    }

                } catch (\Exception $e) {
                    $error = 'Failed to convert to GMC format: ' . $e->getMessage();
                    $this->logger->error($error);
                    $result['errors'][] = $error;
                }

            }

            // Note: Files are NOT deleted from Bazaarvoice FTP server to preserve data
            $this->logger->info('File processed successfully, keeping original on FTP server: ' . $filename);

        } catch (\Exception $e) {
            $error = 'Failed to process file ' . $filename . ': ' . $e->getMessage();
            $this->logger->error($error);
            $result['errors'][] = $error;
        }

        return $result;
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Test FTP connection
     *
     * @param int|null $storeId
     * @return array
     */
    public function testConnection($storeId = null): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'files_found' => 0
        ];

        try {
            if (!$this->ftpService->isEnabled($storeId)) {
                throw new LocalizedException(__('FTP import is not enabled.'));
            }

            $this->ftpService->connect($storeId);

            // Test connection by trying to get file list
            $files = $this->ftpService->getFileList($storeId);
            $this->ftpService->disconnect();

            $result['success'] = true;
            $result['message'] = __('Connection successful. Found %1 files.', count($files));
            $result['files_found'] = count($files);

        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get import statistics
     *
     * @param int|null $storeId
     * @return array
     */
    public function getImportStatistics($storeId = null): array
    {
        $localPath = $this->fileProcessor->getAbsoluteLocalPath($storeId);
        $extractPath = $localPath . '/extracted/';

        $stats = [
            'local_path' => $localPath,
            'extract_path' => $extractPath,
            'zip_files' => 0,
            'extracted_files' => 0,
            'total_size' => 0
        ];

        try {
            if ($this->fileProcessor->fileDriver->isExists($localPath)) {
                $files = $this->fileProcessor->fileDriver->readDirectory($localPath);
                foreach ($files as $file) {
                    if ($this->fileProcessor->fileDriver->isFile($file)) {
                        if ($this->fileProcessor->isZipFile(basename($file))) {
                            $stats['zip_files']++;
                        }

                        $stats['total_size'] += $this->fileProcessor->fileDriver->stat($file)['size'];
                    }

                }

            }

            if ($this->fileProcessor->fileDriver->isExists($extractPath)) {
                $extractedFiles = $this->fileProcessor->fileDriver->readDirectory($extractPath);
                foreach ($extractedFiles as $file) {
                    if ($this->fileProcessor->fileDriver->isFile($file)) {
                        $stats['extracted_files']++;
                    }

                }

            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to get import statistics: ' . $e->getMessage());
        }

        return $stats;
    }
}
