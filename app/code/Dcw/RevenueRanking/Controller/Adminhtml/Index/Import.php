<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Exception\LocalizedException;
use Dcw\RevenueRanking\Model\ResourceModel\RevenueRanking\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Catalog\Model\ResourceModel\Product\ActionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Dcw\RevenueRanking\Model\RevenueRankingService;
use Magento\Eav\Model\Config as EavConfig;

class Import extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Csv
     */
    protected $csvProcessor;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var FormKeyValidator
     */
    protected $formKeyValidator;

    /**
     * @var ActionFactory
     */
    protected $productActionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
	
	protected $revenueRankingService;
	
	/**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Csv $csvProcessor
     * @param CollectionFactory $collectionFactory
     * @param ProductRepository $productRepository
     * @param FormKeyValidator $formKeyValidator
     * @param ActionFactory $productActionFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Csv $csvProcessor,
        CollectionFactory $collectionFactory,
        ProductRepository $productRepository,
        FormKeyValidator $formKeyValidator,
        ActionFactory $productActionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
		RevenueRankingService $revenueRankingService,
		EavConfig $eavConfig
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->csvProcessor = $csvProcessor;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->formKeyValidator = $formKeyValidator;
        $this->productActionFactory = $productActionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
		$this->revenueRankingService = $revenueRankingService;
		$this->eavConfig = $eavConfig;
        parent::__construct($context);
    }

    /**
     * Import manual adjustments from CSV
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->jsonError($result, __('Invalid form key. Please refresh the page.'));
        }

        try {
            $csvFile = $this->getCsvFile();
            $csvData = $this->getCsvData($csvFile);
            [$skuIndex, $manualAdjustmentIndex] = $this->validateCsvHeaders($csvData[0]);

            [$updatedCount, $errors, $productsToUpdate] =
                $this->processCsvRows($csvData, $skuIndex, $manualAdjustmentIndex);

            $attributeUpdatedCount = !empty($productsToUpdate)
                ? $this->updateProductAttributes($productsToUpdate)
                : 0;

            return $this->prepareFinalResult(
                $result,
                $updatedCount,
                $attributeUpdatedCount,
                $errors
            );
        } catch (\Exception $e) {
            $this->logger->error('Manual adjustment import failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine()
            ]);
            return $this->jsonError($result, $e->getMessage());
        }
    }

    /**
     * Retrieve uploaded CSV file
     *
     * @return array
     * @throws LocalizedException
     */
    private function getCsvFile(): array
    {
        $csvFile = $this->getRequest()->getFiles('csv_file');
        if (!$csvFile || !isset($csvFile['tmp_name'])) {
            throw new LocalizedException(__('No CSV file uploaded.'));
        }
        return $csvFile;
    }

    /**
     * Get CSV data rows
     *
     * @param array $csvFile
     * @return array
     * @throws LocalizedException
     */
    private function getCsvData(array $csvFile): array
    {
        $data = $this->csvProcessor->getData($csvFile['tmp_name']);
        if (empty($data) || count($data) < 2) {
            throw new LocalizedException(__('CSV file is empty or invalid.'));
        }
        return $data;
    }

    /**
     * Validate CSV headers and return column indexes
     *
     * @param array $headers
     * @return array [skuIndex, manualAdjustmentIndex]
     * @throws LocalizedException
     */
    private function validateCsvHeaders(array $headers): array
    {
        $headers = array_map('strtolower', array_map('trim', $headers));
        $skuIndex = array_search('sku', $headers);
        $manualAdjustmentIndex = array_search('manual_adjustment', $headers);

        if ($skuIndex === false || $manualAdjustmentIndex === false) {
            throw new LocalizedException(__('CSV must contain "sku" and "manual_adjustment" columns.'));
        }
        return [$skuIndex, $manualAdjustmentIndex];
    }

    /**
     * Process all rows from CSV and prepare data for update
     *
     * @param array $csvData
     * @param int $skuIndex
     * @param int $manualAdjustmentIndex
     * @return array [updatedCount, errors, productsToUpdate]
     */
    private function processCsvRows(array $csvData, int $skuIndex, int $manualAdjustmentIndex): array
    {
        $updatedCount = 0;
        $errors = [];
        $productsToUpdate = [];

        $rowCount = count($csvData);
        for ($i = 1; $i < $rowCount; $i++) {
            $row = $csvData[$i];
            try {
                [$productId, $adjustedRevenue] = $this->processRow(
                    $row,
                    $i,
                    $skuIndex,
                    $manualAdjustmentIndex
                );
                if ($productId) {
                    $productsToUpdate[$productId] = $adjustedRevenue;
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }
        return [$updatedCount, $errors, $productsToUpdate];
    }

    /**
     * Process a single CSV row and update revenue ranking record
     *
     * @param array $row
     * @param int $rowIndex
     * @param int $skuIndex
     * @param int $manualAdjustmentIndex
     * @return array [productId, adjustedRevenue]
     * @throws LocalizedException
     */
    private function processRow(array $row, int $rowIndex, int $skuIndex, int $manualAdjustmentIndex): array
    {
        if (count($row) < max($skuIndex, $manualAdjustmentIndex) + 1) {
            throw new LocalizedException(__('Insufficient columns'));
        }

        $sku = trim($row[$skuIndex]);
        $manualAdjustment = trim($row[$manualAdjustmentIndex]);

        if (empty($sku)) {
            throw new LocalizedException(__('SKU is required'));
        }

        if (!empty($manualAdjustment) && !is_numeric($manualAdjustment)) {
            throw new LocalizedException(__('Manual adjustment must be numeric'));
        }

        $product = $this->productRepository->get($sku);
        $productId = $product->getId();

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId);

        if (!$collection->getSize()) {
            throw new LocalizedException(__('No revenue ranking record found for SKU %1', $sku));
        }

        foreach ($collection as $item) {
            $adjustmentValue = !empty($manualAdjustment) ? $manualAdjustment : null;
            $baseRevenue = $item->getBaseRevenue();
            $adjustedRevenue = $baseRevenue + ($adjustmentValue ?: 0);

            $item->setManualAdjustment($adjustmentValue)
                ->setAdjustedRevenue($adjustedRevenue)
                ->setSku($sku)
                ->save();

            $this->logger->info('Updated manual adjustment', [
                'sku' => $sku,
                'product_id' => $productId,
                'manual_adjustment' => $manualAdjustment
            ]);
        }

        return [$productId, $adjustedRevenue ?? null];
    }

    /**
     * Prepare final JSON response
     *
     * @param \Magento\Framework\Controller\Result\Json $result
     * @param int $updatedCount
     * @param int $attributeUpdatedCount
     * @param array $errors
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function prepareFinalResult($result, int $updatedCount, int $attributeUpdatedCount, array $errors)
    {
        if (!empty($errors)) {
            $message = __(
                'Import completed with %1 errors. %2 records updated, %3 product attributes updated. Details: %4',
                count($errors),
                $updatedCount,
                $attributeUpdatedCount,
                implode(', ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '...' : '')
            );
            return $result->setData([
                'success' => false,
                'message' => $message,
                'updated_count' => $updatedCount,
                'attribute_updated_count' => $attributeUpdatedCount,
                'errors' => $errors
            ]);
        }

        $message = __(
            'Import completed successfully. %1 records updated, %2 product attributes updated.',
            $updatedCount,
            $attributeUpdatedCount
        );
        return $result->setData([
            'success' => true,
            'message' => $message,
            'updated_count' => $updatedCount,
            'attribute_updated_count' => $attributeUpdatedCount
        ]);
    }

    /**
     * Return JSON error response
     *
     * @param \Magento\Framework\Controller\Result\Json $result
     * @param string|\Magento\Framework\Phrase $message
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function jsonError($result, $message)
    {
        return $result->setData(['success' => false, 'message' => $message]);
    }

    /**
     * Update product attributes with revenue data
     *
     * @param array $productsToUpdate Array of product_id => manual_adjustment_value
     * @return int
     */
    private function updateProductAttributes($productsToUpdate)
    {
		$connection = $this->revenueRankingService->getResourceConnection()->getConnection();
		$attribute = $this->eavConfig->getAttribute('catalog_product', 'revenue_ranking');
		$attributeId = $attribute->getId();
		$backendType = $attribute->getBackendType();
		$eavTable  = $this->revenueRankingService->getResourceConnection()->getTableName('catalog_product_entity_' . $backendType);
        try {
            $this->logger->info('Starting product attribute update', [
                'product_count' => count($productsToUpdate)
            ]);

            $storeIds = array_keys($this->storeManager->getStores());
			$storeIds[] = 0;
            $updatedCount = 0;

            foreach ($storeIds as $storeId) {

                foreach ($productsToUpdate as $productId => $adjustedRevenue) {
                    // Use the adjusted revenue value directly
                    if ($adjustedRevenue !== null) {
						$sql = "
						UPDATE {$eavTable} AS t
						JOIN catalog_product_entity AS e ON t.row_id = e.row_id
						SET t.value = :value
						WHERE t.attribute_id = :attribute_id
						  AND e.entity_id = :entity_id
						  AND t.store_id = :store_id
					";

					$connection->query($sql, [
						'value'        => $adjustedRevenue,
						'attribute_id' => $attributeId,
						'entity_id'    => $productId,
						'store_id'     => $storeId
					]);
                        $updatedCount++;
                    }
                }
            }

            $this->logger->info('Product attribute update completed', [
                'updated_count' => $updatedCount
            ]);

            return $updatedCount;
        } catch (\Exception $e) {
            $this->logger->error('Error updating product attributes', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return 0;
        }
    }

    /**
     * Check if user has permission to import
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dcw_RevenueRanking::revenue_ranking');
    }
}
