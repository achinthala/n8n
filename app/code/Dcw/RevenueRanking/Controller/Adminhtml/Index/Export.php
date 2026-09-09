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
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking\Grid\CollectionFactory;

class Export extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->fileFactory = $fileFactory;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Export revenue ranking data to CSV
     *
     * @return ResponseInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            
            $csvData = [];
            $csvData[] = ['ID', 'Product ID', 'Base Revenue', 'Adjusted Revenue', 'Manual Adjustment', 'Last Updated'];
            
            foreach ($collection->getItems() as $item) {
                $csvData[] = [
                    $item->getId(),
                    $item->getProductId(),
                    $item->getBaseRevenue(),
                    $item->getAdjustedRevenue(),
                    $item->getManualAdjustment(),
                    $item->getLastUpdated()
                ];
            }
            
            $fileName = 'revenue_ranking_' . date('Y-m-d_H-i-s') . '.csv';
            
            return $this->fileFactory->create(
                $fileName,
                $csvData,
                DirectoryList::VAR_DIR,
                'text/csv'
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while exporting data: %1', $e->getMessage()));
            return $this->_redirect('revenue_ranking/index/index');
        }
    }

    /**
     * Check if user has permission to export
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dcw_RevenueRanking::revenue_ranking');
    }
}
