<?php

/**
 * Bazaarvoice FTP Import Execute Controller
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Controller\Adminhtml\Import;

use Dcw\BazaarvoiceFtpImport\Model\ImportService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class Execute extends Action
{
    /**
     * @var ImportService
     */
    private $importService;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * Execute constructor.
     *
     * @param Context $context
     * @param ImportService $importService
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        ImportService $importService,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->importService = $importService;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $storeId = $this->getRequest()->getParam('store_id');
            $importResult = $this->importService->execute($storeId);

            if ($importResult['success']) {
                $message = sprintf(
                    'Import completed successfully. Processed: %d files, Downloaded: %d files, Extracted: %d files',
                    $importResult['files_processed'],
                    $importResult['files_downloaded'],
                    $importResult['files_extracted']
                );

                if (!empty($importResult['errors'])) {
                    $message .= ' (with some warnings)';
                }

                $result->setData([
                    'success' => true,
                    'message' => $message,
                    'data' => $importResult
                ]);
            } else {
                $result->setData([
                    'success' => false,
                    'message' => 'Import failed: ' . implode(', ', $importResult['errors']),
                    'data' => $importResult
                ]);
            }

        } catch (LocalizedException $e) {
            $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $result->setData([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Check if user has permission to execute this action
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dcw_BazaarvoiceFtpImport::config');
    }
}
