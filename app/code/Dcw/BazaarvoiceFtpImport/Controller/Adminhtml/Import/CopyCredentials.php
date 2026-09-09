<?php

/**
 * Copy Credentials Controller
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CopyCredentials extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            // Get credentials from official Bazaarvoice module
            $bvUsername = $this->scopeConfig->getValue(
                'bazaarvoice/feeds/sftp_username',
                ScopeInterface::SCOPE_STORE
            );
            $bvPassword = $this->scopeConfig->getValue(
                'bazaarvoice/feeds/sftp_password',
                ScopeInterface::SCOPE_STORE
            );
            $bvHost = $this->scopeConfig->getValue(
                'bazaarvoice/feeds/sftp_host_name',
                ScopeInterface::SCOPE_STORE
            );

            $copiedCount = 0;

            // Copy credentials to our module (save at default scope)
            if (!empty($bvUsername)) {
                $this->configWriter->save(
                    'bazaarvoice_ftp/ftp_settings/username',
                    $bvUsername,
                    'default',
                    0
                );
                $copiedCount++;
            }

            if (!empty($bvPassword)) {
                $this->configWriter->save(
                    'bazaarvoice_ftp/ftp_settings/password',
                    $bvPassword,
                    'default',
                    0
                );
                $copiedCount++;
            }

            if (!empty($bvHost)) {
                // Get the Bazaarvoice environment to determine correct host
                $bvEnvironment = $this->scopeConfig->getValue(
                    'bazaarvoice/general/environment',
                    ScopeInterface::SCOPE_STORE
                );

                // Fix hostname based on environment
                if ($bvHost === 'sftp') {
                    $hostValue = ($bvEnvironment === 'staging') ? 'sftp-stg.bazaarvoice.com' : 'sftp.bazaarvoice.com';
                } else {
                    $hostValue = $bvHost;
                }

                $this->configWriter->save(
                    'bazaarvoice_ftp/ftp_settings/host',
                    $hostValue,
                    'default',
                    0
                );
                $copiedCount++;
            }

            if ($copiedCount > 0) {
                $result->setData([
                    'success' => true,
                    'message' => "Successfully copied $copiedCount credential(s) from official Bazaarvoice module.",
                    'data' => [
                        'host' => $hostValue ?? null,
                        'username' => $bvUsername ?? null,
                        'password' => $bvPassword ? '***' : null
                    ]
                ]);
            } else {
                $result->setData([
                    'success' => false,
                    'message' => 'No credentials found in official Bazaarvoice module.'
                ]);
            }

        } catch (\Exception $e) {
            $result->setData([
                'success' => false,
                'message' => 'Error copying credentials: ' . $e->getMessage()
            ]);
        }

        return $result;
    }
}
