<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

use Dcw\ProductDataValidation\Model\ResourceModel\Result\CollectionFactory as ResultCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds summary statistics for a completed validation job.
 */
class ReportSummary
{
    public function __construct(
        private readonly JobManager $jobManager,
        private readonly ResultCollectionFactory $resultCollectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return array{
     *     job: Job,
     *     total_eligible: int,
     *     products_checked: int,
     *     products_with_issues: int,
     *     products_passed: int,
     *     execution_time: float|null,
     *     status: string,
     *     attribute_breakdown: array<string, int>,
     *     store_labels: string
     * }
     */
    public function getSummary(int $jobId): array
    {
        $job = $this->jobManager->getJob($jobId);
        $breakdown = $job->getAttributeBreakdown();
        if ($breakdown === []) {
            $breakdown = $this->rebuildBreakdown($jobId);
        }

        $storeIds = array_filter(array_map('intval', explode(',', (string) $job->getData('store_ids'))));
        $storeLabels = [];
        foreach ($storeIds as $storeId) {
            try {
                $storeLabels[] = $this->storeManager->getStore($storeId)->getName();
            } catch (\Throwable) {
                $storeLabels[] = (string) $storeId;
            }
        }

        return [
            'job' => $job,
            'total_eligible' => (int) $job->getData('total_eligible'),
            'products_checked' => (int) $job->getData('products_checked'),
            'products_with_issues' => (int) $job->getData('products_with_issues'),
            'products_passed' => (int) $job->getData('products_passed'),
            'execution_time' => $job->getData('execution_time') !== null
                ? (float) $job->getData('execution_time')
                : null,
            'status' => $job->getStatus(),
            'attribute_breakdown' => $breakdown,
            'store_labels' => implode(', ', $storeLabels),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function rebuildBreakdown(int $jobId): array
    {
        $collection = $this->resultCollectionFactory->create();
        $collection->addFieldToFilter('job_id', $jobId);
        $breakdown = [];
        foreach ($collection as $row) {
            $codes = array_filter(array_map('trim', explode(',', (string) $row->getData('missing_attributes'))));
            foreach ($codes as $code) {
                $breakdown[$code] = ($breakdown[$code] ?? 0) + 1;
            }
        }
        arsort($breakdown);

        return $breakdown;
    }
}
