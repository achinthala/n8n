<?php

/**
 * Bazaarvoice File Processor
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class FileProcessor
{
    const XML_PATH_KEEP_ORIGINAL_FILES = 'bazaarvoice_ftp/import_settings/keep_original_files';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * FileProcessor constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param File $fileDriver
     * @param Filesystem $filesystem
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        File $fileDriver,
        Filesystem $filesystem
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->fileDriver = $fileDriver;
        $this->filesystem = $filesystem;
    }

    /**
     * Get local import path - always use media folder
     *
     * @param int|null $storeId
     * @return string
     */
    public function getLocalPath($storeId = null): string
    {
        try {
            // Always use pub/media for media files
            $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            return $mediaPath . 'bazaarvoice';
        } catch (\Exception $e) {
            $this->logger->error('Could not access media directory: ' . $e->getMessage());
            // Fallback to relative path if absolute path fails
            return 'pub/media/bazaarvoice';
        }
    }



    /**
     * Get absolute local path
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAbsoluteLocalPath($storeId = null): string
    {
        // getLocalPath() already returns absolute path
        return $this->getLocalPath($storeId);
    }

    /**
     * Create local directory if it doesn't exist
     *
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function createLocalDirectory($storeId = null): bool
    {
        $absolutePath = $this->getAbsoluteLocalPath($storeId);


        if(!$this->fileDriver->isExists($absolutePath)) {
            try {
                $this->fileDriver->createDirectory($absolutePath, 0755);
                $this->logger->info('Created local directory: ' . $absolutePath);
            } catch (\Exception $e) {
                throw new LocalizedException(__('Failed to create local directory: %1. Error: %2', $absolutePath, $e->getMessage()));
            }

        }

        // Also create the extracted subdirectory
        $extractPath = $absolutePath . '/extracted/';

        if(!$this->fileDriver->isExists($extractPath)) {
            try {
                $this->fileDriver->createDirectory($extractPath, 0755);
                $this->logger->info('Created extracted directory: ' . $extractPath);
            } catch (\Exception $e) {
                throw new LocalizedException(__('Failed to create extracted directory: %1. Error: %2', $extractPath, $e->getMessage()));
            }

        }

        return true;
    }

    /**
     * Extract zip file
     *
     * @param string $zipFile
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    public function extractZipFile(string $zipFile, $storeId = null): array
    {
        $absolutePath = $this->getAbsoluteLocalPath($storeId);
        $fullZipPath = $absolutePath . '/' . $zipFile;


        if(!$this->fileDriver->isExists($fullZipPath)) {
            throw new LocalizedException(__('Zip file does not exist: %1', $zipFile));
        }

        $this->logger->info('Extracting zip file: ' . $zipFile);

        $zip = new \ZipArchive();
        $result = $zip->open($fullZipPath);


        if($result !== TRUE) {
            throw new LocalizedException(__('Failed to open zip file: %1. Error code: %2', $zipFile, $result));
        }

        $extractedFiles = [];
        $extractPath = $absolutePath . '/extracted/';

        // Create extracted directory if it doesn't exist

        if(!$this->fileDriver->isExists($extractPath)) {
            try {
                $this->fileDriver->createDirectory($extractPath, 0755);
                $this->logger->info('Created extracted directory: ' . $extractPath);
            } catch (\Exception $e) {
                $this->logger->error('Failed to create extracted directory: ' . $extractPath . ' - ' . $e->getMessage());
                throw new LocalizedException(__('Failed to create extracted directory: %1. Error: %2', $extractPath, $e->getMessage()));
            }

        }

        // Extract all files

        for($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename !== false) {
                $zip->extractTo($extractPath, $filename);
                $extractedFiles[] = $filename;
            }

        }

        $zip->close();

        $this->logger->info('Extracted ' . count($extractedFiles) . ' files from: ' . $zipFile);

        return $extractedFiles;
    }

    /**
     * Decompress gzip file
     *
     * @param string $gzipFile
     * @param int|null $storeId
     * @return string
     * @throws LocalizedException
     */
    public function decompressGzipFile(string $gzipFile, $storeId = null): string
    {
        $absolutePath = $this->getAbsoluteLocalPath($storeId);
        $fullGzipPath = $absolutePath . '/' . $gzipFile;


        if(!$this->fileDriver->isExists($fullGzipPath)) {
            throw new LocalizedException(__('Gzip file does not exist: %1', $gzipFile));
        }

        $this->logger->info('Decompressing gzip file: ' . $gzipFile);

        // Create extracted directory if it doesn't exist
        $extractPath = $absolutePath . '/extracted/';

        if(!$this->fileDriver->isExists($extractPath)) {
            try {
                $this->fileDriver->createDirectory($extractPath, 0755);
                $this->logger->info('Created extracted directory: ' . $extractPath);
            } catch (\Exception $e) {
                $this->logger->error('Failed to create extracted directory: ' . $extractPath . ' - ' . $e->getMessage());
                throw new LocalizedException(__('Failed to create extracted directory: %1. Error: %2', $extractPath, $e->getMessage()));
            }

        }

        // Determine output filename (remove .gz extension)
        $outputFilename = preg_replace('/\.gz$/', '', $gzipFile);
        $outputPath = $extractPath . $outputFilename;

        // Open gzip file for reading
        $gzipHandle = gzopen($fullGzipPath, 'rb');

        if(!$gzipHandle) {
            throw new LocalizedException(__('Failed to open gzip file: %1', $gzipFile));
        }

        // Open output file for writing
        $outputHandle = fopen($outputPath, 'wb');

        if(!$outputHandle) {
            gzclose($gzipHandle);
            throw new LocalizedException(__('Failed to create output file: %1', $outputFilename));
        }

        // Decompress and write to output file
        $bytesWritten = 0;

        while(!gzeof($gzipHandle)) {
            $data = gzread($gzipHandle, 8192);
            if ($data === false) {
                gzclose($gzipHandle);
                fclose($outputHandle);
                throw new LocalizedException(__('Failed to read from gzip file: %1', $gzipFile));
            }

            $bytesWritten += fwrite($outputHandle, $data);
        }

        gzclose($gzipHandle);
        fclose($outputHandle);

        $this->logger->info('Decompressed gzip file: ' . $gzipFile . ' to ' . $outputFilename . ' (' . $this->formatBytes($bytesWritten) . ')');

        return $outputFilename;
    }

    /**
     * Remove compressed file after extraction/decompression
     *
     * @param string $compressedFile
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function removeCompressedFile(string $compressedFile, $storeId = null): bool
    {
        $keepOriginal = (bool) $this->scopeConfig->getValue(
            self::XML_PATH_KEEP_ORIGINAL_FILES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );


        if($keepOriginal) {
            $this->logger->info('Keeping original compressed file: ' . $compressedFile);
            return true;
        }

        $absolutePath = $this->getAbsoluteLocalPath($storeId);
        $fullFilePath = $absolutePath . '/' . $compressedFile;


        if($this->fileDriver->isExists($fullFilePath)) {
            try {
                $this->fileDriver->deleteFile($fullFilePath);
                $this->logger->info('Removed compressed file: ' . $compressedFile);
                return true;
            } catch (\Exception $e) {
                $this->logger->error('Failed to remove compressed file: ' . $compressedFile . '. Error: ' . $e->getMessage());
                return false;
            }

        }

        return false;}

    /**
     * Remove zip file after extraction (legacy method for backward compatibility)
     *
     * @param string $zipFile
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function removeZipFile(string $zipFile, $storeId = null): bool
    {
        return false;}

    /**
     * Get file extension
     *
     * @param string $filename
     * @return string
     */
    public function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Check if file is a zip file
     *
     * @param string $filename
     * @return bool
     */
    public function isZipFile(string $filename): bool
    {
        return $this->getFileExtension($filename) === 'zip';
    }

    /**
     * Check if file is a gzip file
     *
     * @param string $filename
     * @return bool
     */
    public function isGzipFile(string $filename): bool
    {
        return $this->getFileExtension($filename) === 'gz';
    }

    /**
     * Check if file is a compressed file (zip or gzip)
     *
     * @param string $filename
     * @return bool
     */
    public function isCompressedFile(string $filename): bool
    {
        return $this->isZipFile($filename) || $this->isGzipFile($filename);
    }

    /**
     * Get file size in human readable format
     *
     * @param string $filepath
     * @return string
     */
    public function getHumanReadableFileSize(string $filepath): string
    {

        if(!$this->fileDriver->isExists($filepath)) {
            return '0 B';
        }

        $size = $this->fileDriver->stat($filepath)['size'];
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];


        for($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Clean up old extracted files
     *
     * @param int $daysOld
     * @param int|null $storeId
     * @return int
     */
    public function cleanupOldFiles(int $daysOld = 7, $storeId = null): int
    {
        $absolutePath = $this->getAbsoluteLocalPath($storeId);
        $extractPath = $absolutePath . '/extracted/';


        if(!$this->fileDriver->isExists($extractPath)) {
            return 0;
        }

        $files = $this->fileDriver->readDirectory($extractPath);
        $deletedCount = 0;
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);


        foreach($files as $file) {
            if ($this->fileDriver->isFile($file)) {
                $fileTime = $this->fileDriver->stat($file)['mtime'];
                if ($fileTime < $cutoffTime) {
                    try {
                        $this->fileDriver->deleteFile($file);
                        $deletedCount++;
                    } catch (\Exception $e) {
                        $this->logger->error('Failed to delete old file: ' . $file . '. Error: ' . $e->getMessage());
                    }

                }

            }

        }


        if($deletedCount > 0) {
            $this->logger->info('Cleaned up ' . $deletedCount . ' old files');
        }

        return $deletedCount;
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
}
