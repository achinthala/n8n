<?php

/**
 * Bazaarvoice FTP Service
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use phpseclib3\Net\SFTP;

class FtpService
{
    const XML_PATH_FTP_ENABLED = 'bazaarvoice_ftp/ftp_settings/enabled';
    const XML_PATH_FTP_HOST = 'bazaarvoice_ftp/ftp_settings/host';
    const XML_PATH_FTP_USERNAME = 'bazaarvoice_ftp/ftp_settings/username';
    const XML_PATH_FTP_PASSWORD = 'bazaarvoice_ftp/ftp_settings/password';
    const XML_PATH_FTP_REMOTE_PATH = 'bazaarvoice_ftp/ftp_settings/remote_path';
    const XML_PATH_FTP_FILE_PATTERN = 'bazaarvoice_ftp/ftp_settings/file_pattern';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SFTP|null
     */
    private $sftpConnection;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * FtpService constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
    }

    /**
     * Check if FTP import is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_FTP_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Connect to FTP/SFTP server
     *
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function connect($storeId = null): bool
    {

        if(!$this->isEnabled($storeId)) {
            throw new LocalizedException(__('FTP import is not enabled.'));
        }

        $host = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_HOST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // Hardcoded to use SFTP protocol and port 22
        $protocol = 'sftp';
        $port = 22;


        if(empty($host)) {
            throw new LocalizedException(__('Server host is not configured.'));
        }

        $this->logger->info('Connecting to ' . strtoupper($protocol) . ' server: ' . $host . ':' . $port);

        // Always use SFTP
        return $this->connectSftp($host, $port, $storeId);
    }

    /**
     * Connect to SFTP server using Magento's native SFTP class
     *
     * @param string $host
     * @param int $port
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    private function connectSftp(string $host, int $port, $storeId = null): bool
    {
        $username = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $encryptedPassword = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );


        if(empty($username) || empty($encryptedPassword)) {
            throw new LocalizedException(__('SFTP credentials are not configured.'));
        }

        // Decrypt the password
        $password = $this->encryptor->decrypt($encryptedPassword);

        try {
            $this->logger->info('Attempting SFTP connection to: ' . $host . ':' . $port . ' with username: ' . $username);

            $this->sftpConnection = new SFTP($host, $port);
            $this->sftpConnection->setTimeout(30);

            $this->logger->info('SFTP instance created, attempting login...');
            $loginResult = $this->sftpConnection->login($username, $password);

            if (!$loginResult) {
                $error = $this->sftpConnection->getLastError();
                $this->logger->error('SFTP login failed. Last error: ' . ($error ?: 'No error message available'));
                $this->logger->error('SFTP connection state: ' . ($this->sftpConnection->isConnected() ? 'Connected' : 'Not connected'));
                $this->logger->error('SFTP authentication state: ' . ($this->sftpConnection->isAuthenticated() ? 'Authenticated' : 'Not authenticated'));

                // Try to get more detailed error information
                $serverIdentification = $this->sftpConnection->getServerIdentification();
                $this->logger->error('Server identification: ' . ($serverIdentification ?: 'Not available'));

                // Get SSH errors if available
                $sshErrors = $this->sftpConnection->getErrors();
                if (!empty($sshErrors)) {
                    $this->logger->error('SSH errors: ' . implode(', ', $sshErrors));
                }

                // Create a more descriptive error message
                $errorMessage = 'Authentication failed';
                if ($error) {
                    $errorMessage .= ': ' . $error;
                } else {
                    $errorMessage .= ' - Check username and password';
                }

                throw new LocalizedException(__('SFTP login failed: %1', $errorMessage));
            }

            $this->logger->info('Successfully connected to SFTP server using phpseclib');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to connect to SFTP server: ' . $e->getMessage());
            $this->logger->error('Exception type: ' . get_class($e));
            $this->logger->error('SFTP connection details - Host: ' . $host . ', Port: ' . $port . ', Username: ' . $username);
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            throw new LocalizedException(__('Failed to connect to SFTP server: %1', $e->getMessage()));
        }

    }

    /**
     * Disconnect from SFTP server
     */
    public function disconnect(): void
    {

        if($this->sftpConnection) {
            $this->sftpConnection->disconnect();
            $this->sftpConnection = null;
            $this->logger->info('Disconnected from SFTP server');
        }

    }

    /**
     * Get list of files matching the pattern
     *
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getFileList($storeId = null): array
    {

        if(!$this->sftpConnection) {
            throw new LocalizedException(__('Not connected to SFTP server.'));
        }

        return $this->getSftpFileList($storeId);
    }


    /**
     * Get SFTP file list using Magento's native SFTP class
     *
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    private function getSftpFileList($storeId = null): array
    {

        if(!$this->sftpConnection) {
            throw new LocalizedException(__('SFTP connection not established.'));
        }

        $remotePath = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_REMOTE_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '/';

        $filePattern = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_FILE_PATTERN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '*.xml.gz';

        $this->logger->info('Getting SFTP file list from: ' . $remotePath . ' with pattern: ' . $filePattern);

        try {
            $files = $this->sftpConnection->nlist($remotePath);
            if ($files === false) {
                throw new LocalizedException(__('Failed to list files from SFTP directory: %1', $remotePath));
            }

            // Filter out . and .. entries
            $files = array_filter($files, function($file) {
                return $file !== '.' && $file !== '..';
            });

            // Filter files by pattern
            $filteredFiles = array_filter($files, function($file) use ($filePattern) {
                return fnmatch($filePattern, $file);
            });

            return array_values($filteredFiles);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get SFTP file list: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to get SFTP file list: %1', $e->getMessage()));
        }

    }

    /**
     * Filter files by pattern
     *
     * @param array $files
     * @param string $filePattern
     * @return array
     */
    private function filterFiles(array $files, string $filePattern): array
    {
        $filteredFiles = [];
        $pattern = str_replace('*', '.*', $filePattern);
        $pattern = '/^' . $pattern . '$/i';


        foreach($files as $file) {
            $fileName = basename($file);
            if (preg_match($pattern, $fileName)) {
                $filteredFiles[] = $fileName;
            }

        }

        $this->logger->info('Found ' . count($filteredFiles) . ' files matching pattern');

        return $filteredFiles;
    }

    /**
     * Download file from FTP/SFTP server
     *
     * @param string $remoteFile
     * @param string $localFile
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function downloadFile(string $remoteFile, string $localFile, $storeId = null): bool
    {
        // Always use SFTP
        return $this->downloadSftpFile($remoteFile, $localFile, $storeId);
    }


    /**
     * Download file from SFTP server using Magento's native SFTP class
     *
     * @param string $remoteFile
     * @param string $localFile
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    private function downloadSftpFile(string $remoteFile, string $localFile, $storeId = null): bool
    {

        if(!$this->sftpConnection) {
            throw new LocalizedException(__('SFTP connection not established.'));
        }

        $remotePath = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_REMOTE_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '/';

        $fullRemotePath = rtrim($remotePath, '/') . '/' . $remoteFile;

        $this->logger->info('Downloading SFTP file: ' . $fullRemotePath . ' to ' . $localFile);

        try {
            // Create local directory if it doesn't exist
            $localDir = dirname($localFile);
            if (!is_dir($localDir)) {
                mkdir($localDir, 0755, true);
            }

            // Download file using phpseclib
            $content = $this->sftpConnection->get($fullRemotePath, $localFile);
            if ($content === false) {
                throw new LocalizedException(__('Failed to download file from SFTP server: %1', $fullRemotePath));
            }

            // Check if file was downloaded successfully
            if (!file_exists($localFile)) {
                throw new LocalizedException(__('Downloaded file not found: %1', $localFile));
            }

            $fileSize = filesize($localFile);
            $this->logger->info('Successfully downloaded SFTP file: ' . $remoteFile . ' (' . $fileSize . ' bytes)');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to download SFTP file: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to download SFTP file: %1', $e->getMessage()));
        }

    }

    /**
     * Delete file from FTP/SFTP server
     *
     * @param string $remoteFile
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function deleteFile(string $remoteFile, $storeId = null): bool
    {
        // Always use SFTP
        return $this->deleteSftpFile($remoteFile, $storeId);
    }


    /**
     * Delete file from SFTP server using Magento's native SFTP class
     *
     * @param string $remoteFile
     * @param int|null $storeId
     * @return bool
     * @throws LocalizedException
     */
    private function deleteSftpFile(string $remoteFile, $storeId = null): bool
    {

        if(!$this->sftpConnection) {
            throw new LocalizedException(__('SFTP connection not established.'));
        }

        $remotePath = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_REMOTE_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '/';

        $fullRemotePath = rtrim($remotePath, '/') . '/' . $remoteFile;

        $this->logger->info('Deleting file from SFTP: ' . $fullRemotePath);

        try {
            $result = $this->sftpConnection->delete($fullRemotePath);
            if ($result === false) {
                $this->logger->warning('Failed to delete file from SFTP: ' . $remoteFile);
                return false;
            }

            $this->logger->info('Successfully deleted file from SFTP: ' . $remoteFile);
            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to delete file from SFTP: ' . $remoteFile . ' - ' . $e->getMessage());
            return false;
        }

    }

    /**
     * Get file size from FTP/SFTP server
     *
     * @param string $remoteFile
     * @param int|null $storeId
     * @return int
     * @throws LocalizedException
     */
    public function getFileSize(string $remoteFile, $storeId = null): int
    {
        // Always use SFTP
        return $this->getSftpFileSize($remoteFile, $storeId);
    }


    /**
     * Get file size from SFTP server using Magento's native SFTP class
     *
     * @param string $remoteFile
     * @param int|null $storeId
     * @return int
     * @throws LocalizedException
     */
    private function getSftpFileSize(string $remoteFile, $storeId = null): int
    {

        if(!$this->sftpConnection) {
            throw new LocalizedException(__('SFTP connection not established.'));
        }

        $remotePath = $this->scopeConfig->getValue(
            self::XML_PATH_FTP_REMOTE_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: '/';

        $fullRemotePath = rtrim($remotePath, '/') . '/' . $remoteFile;

        try {
            $stat = $this->sftpConnection->stat($fullRemotePath);
            if ($stat === false || !isset($stat['size'])) {
                throw new LocalizedException(__('Failed to get SFTP file size: %1', $remoteFile));
            }

            return (int) $stat['size'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get SFTP file size: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to get SFTP file size: %1', $e->getMessage()));
        }

    }
}
