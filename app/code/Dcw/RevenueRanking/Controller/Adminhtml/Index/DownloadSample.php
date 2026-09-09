<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking\CollectionFactory;

class DownloadSample extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->fileFactory = $fileFactory;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Download sample CSV file
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        try {
            // Get sample data from existing records
            $collection = $this->collectionFactory->create();
            $collection->setPageSize(5);
            $collection->setCurPage(1);

            $csvData = [];
            $csvData[] = ['sku', 'manual_adjustment']; // Header row

            foreach ($collection as $item) {
                $csvData[] = [
                    $item->getSku() ?: 'sample-sku-' . $item->getProductId(),
                    $item->getManualAdjustment() ?: '' // Empty string for null values
                ];
            }

            $fileName = 'manual_adjustment_sample.csv';

            return $this->fileFactory->create(
                $fileName,
                [
                    'type' => 'string',
                    'value' => $this->arrayToCsv($csvData)
                ],
                DirectoryList::VAR_DIR,
                'text/csv'
            );

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error generating sample CSV: %1', $e->getMessage()));
            return $this->_redirect('revenue_ranking/index/index');
        }
    }

    /**
     * Convert array to CSV string
     *
     * @param array $data
     * @return string
     */
    private function arrayToCsv($data)
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Check if user has permission to download sample
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dcw_RevenueRanking::revenue_ranking');
    }
}
