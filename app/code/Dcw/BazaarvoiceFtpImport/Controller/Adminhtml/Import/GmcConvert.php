<?php
/**
 * Manual GMC Convert Controller
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Dcw\BazaarvoiceFtpImport\Model\GmcReviewConverter;
use Dcw\BazaarvoiceFtpImport\Model\FileProcessor;
use Psr\Log\LoggerInterface;

class GmcConvert extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var GmcReviewConverter
     */
    protected $gmcConverter;

    /**
     * @var FileProcessor
     */
    protected $fileProcessor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * GmcConvert constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param GmcReviewConverter $gmcConverter
     * @param FileProcessor $fileProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        GmcReviewConverter $gmcConverter,
        FileProcessor $fileProcessor,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->gmcConverter = $gmcConverter;
        $this->fileProcessor = $fileProcessor;
        $this->logger = $logger;
    }

    /**
     * Execute GMC conversion
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $result = $this->resultJsonFactory->create();

        try {
            $this->logger->info('Starting manual GMC conversion process');

            // Check if GMC conversion is enabled
            if (!$this->gmcConverter->isEnabled()) {
                return $result->setData([
                    'success' => false,
                    'message' => __('GMC conversion is not enabled. Please enable it in the configuration.')
                ]);
            }

            // Get import directory
            $importPath = $this->fileProcessor->getAbsoluteLocalPath();
            $extractPath = $importPath . '/extracted/';

            $filesProcessed = 0;
            $gmcFilesGenerated = 0;
            $errors = [];

            // Process XML files in the extracted directory
            if (is_dir($extractPath)) {
                $xmlFiles = glob($extractPath . '*.xml');

                if (empty($xmlFiles)) {
                    return $result->setData([
                        'success' => false,
                        'message' => __('No XML files found in the extracted directory. Please run the import first.')
                    ]);
                }

                foreach ($xmlFiles as $xmlFile) {
                    try {
                        $this->logger->info('Converting XML file to GMC: ' . basename($xmlFile));
                        $gmcFile = $this->gmcConverter->convertToGmc($xmlFile);

                        if ($gmcFile) {
                            $gmcFilesGenerated++;
                            $this->logger->info('Generated GMC file: ' . $gmcFile);
                        }

                        $filesProcessed++;

                    } catch (\Exception $e) {
                        $error = 'Failed to convert ' . basename($xmlFile) . ': ' . $e->getMessage();
                        $this->logger->error($error);
                        $errors[] = $error;
                    }
                }
            } else {
                return $result->setData([
                    'success' => false,
                    'message' => __('Extracted directory not found. Please run the import first.')
                ]);
            }

            // Get output directory for display
            $outputDir = $this->gmcConverter->getOutputDirectory();

            $this->logger->info("Manual GMC conversion completed. Files processed: {$filesProcessed}, GMC files generated: {$gmcFilesGenerated}");

            return $result->setData([
                'success' => true,
                'message' => __('GMC conversion completed successfully'),
                'files_processed' => $filesProcessed,
                'gmc_files_generated' => $gmcFilesGenerated,
                'output_directory' => $outputDir,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Manual GMC conversion failed: ' . $e->getMessage());

            return $result->setData([
                'success' => false,
                'message' => __('GMC conversion failed: %1', $e->getMessage())
            ]);
        }
    }

    /**
     * Check if user has permission to access this action
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Dcw_BazaarvoiceFtpImport::config');
    }
}
