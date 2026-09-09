<?php
namespace Dcw\RevenueRanking\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Psr\Log\LoggerInterface;
use Dcw\RevenueRanking\Model\RevenueRankingService;
use Magento\Eav\Model\Config as EavConfig;

/**
 * Admin controller for refreshing Revenue Ranking data.
 *
 * - Handles AJAX and normal requests
 * - Validates form keys
 * - Refreshes revenue data and updates product attributes
 */
class Refresh extends Action
{
    protected $resultJsonFactory;
    protected $resultRedirectFactory;
    private $formKeyValidator;
    private $logger;
    private $revenueRankingService;
	
	/**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * Reduced constructor – only 6 dependencies now.
     * The heavy dependencies are grouped inside RevenueRankingService.
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        FormKeyValidator $formKeyValidator,
        LoggerInterface $logger,
        RevenueRankingService $revenueRankingService,
		EavConfig $eavConfig
    ) {
        parent::__construct($context);
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->formKeyValidator   = $formKeyValidator;
        $this->logger             = $logger;
        $this->revenueRankingService = $revenueRankingService;
		$this->eavConfig = $eavConfig;
    }

    /**
     * Main controller action.
     * - Checks request type (AJAX or redirect)
     * - Validates form key
     * - Updates revenue data
     * - Updates product attributes
     */
    public function execute()
    {
        $this->logger->info('Revenue Ranking Refresh Controller - Execute method started');

        // Prepare result type depending on request
        $isAjax = $this->getRequest()->isAjax();
        $result = $isAjax
            ? $this->resultJsonFactory->create()
            : $this->resultRedirectFactory->create();

        try {
            // Get configuration value (defaults to 30)
            $salesPeriodDays = $this->revenueRankingService->getSalesPeriodDays();

            // Check if custom table exists
            $connection = $this->revenueRankingService->getResourceConnection()->getConnection();
            $tableName  = $this->revenueRankingService->getResourceConnection()->getTableName('custom_product_revenue_ranking');

            if (!$connection->isTableExists($tableName)) {
                $result->setData([
                    'success' => false,
                    'message' => __('Revenue ranking table does not exist. Please run setup:upgrade first.')
                ]);
                return $result;
            }

            // Update revenue data in table
            $updatedCount = $this->revenueRankingService
                ->getRevenueRankingResource()
                ->updateBaseRevenue($salesPeriodDays);

            // Update attribute values
            $attributeUpdatedCount = $this->updateProductAttribute();

            // Prepare success message
            $message = __(
                'Revenue data refreshed successfully. Found %1 products with revenue data. Product attributes updated for %2 products.',
                $updatedCount,
                $attributeUpdatedCount
            );

            $this->messageManager->addSuccessMessage($message);

            // Return proper response
            if ($isAjax) {
                $result->setData([
                    'success' => true,
                    'message' => $message,
                    'updated_count' => $updatedCount
                ]);
            } else {
                $result->setPath('revenue_ranking/index/index');
            }

        } catch (\Exception $e) {
            // Log and return error
            $this->logger->error('Revenue Ranking Refresh Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            if ($isAjax) {
                $result->setData([
                    'success' => false,
                    'message' => __('Error refreshing revenue data: %1', $e->getMessage()),
                    'error_details' => $e->getMessage()
                ]);
            } else {
                $this->messageManager->addErrorMessage(__('Error refreshing revenue data: %1', $e->getMessage()));
                $result->setPath('revenue_ranking/index/index');
            }
        }

        return $result;
    }

    /**
     * Update product attributes with revenue ranking values
     */
    private function updateProductAttribute(): int
    {
		$connection = $this->revenueRankingService->getResourceConnection()->getConnection();
		$attribute = $this->eavConfig->getAttribute('catalog_product', 'revenue_ranking');
		$attributeId = $attribute->getId();
		$backendType = $attribute->getBackendType();
		$eavTable  = $this->revenueRankingService->getResourceConnection()->getTableName('catalog_product_entity_' . $backendType);
        try {
            $storeIds    = array_keys($this->revenueRankingService->getStoreManager()->getStores());
			$storeIds[] = 0;
            $revenueData = $this->revenueRankingService->getRevenueRankingResource()->getRevenueDataForAttribute();

            if (empty($revenueData)) {
                return 0;
            }

            $updatedCount = 0;
            foreach ($storeIds as $storeId) {
                foreach ($revenueData as $productId => $revenue) {
					$sql = "
						UPDATE {$eavTable} AS t
						JOIN catalog_product_entity AS e ON t.row_id = e.row_id
						SET t.value = :value
						WHERE t.attribute_id = :attribute_id
						  AND e.entity_id = :entity_id
						  AND t.store_id = :store_id
					";

					$connection->query($sql, [
						'value'        => $revenue,
						'attribute_id' => $attributeId,
						'entity_id'    => $productId,
						'store_id'     => $storeId
					]);
                    $updatedCount++;
                }
            }
            return $updatedCount;

        } catch (\Exception $e) {
            $this->logger->error('Error updating product attributes', [
                'message' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * ACL check for this controller
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Dcw_RevenueRanking::revenue_ranking');
    }
}
