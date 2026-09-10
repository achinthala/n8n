<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Block\Adminhtml\Report;

use Dcw\ProductDataValidation\Model\JobManager;
use Dcw\ProductDataValidation\Model\ReportSummary;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;

/**
 * Report summary header for the latest or requested job.
 */
class Summary extends Template
{
    public function __construct(
        Context $context,
        private readonly ReportSummary $reportSummary,
        private readonly JobManager $jobManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSummaryData(): ?array
    {
        $jobId = (int) $this->getRequest()->getParam('job_id');
        if ($jobId <= 0) {
            $jobId = $this->jobManager->getLatestCompletedJobId();
        }
        if ($jobId <= 0) {
            return null;
        }

        try {
            return $this->reportSummary->getSummary($jobId);
        } catch (LocalizedException) {
            return null;
        }
    }

    public function getJobId(): int
    {
        $data = $this->getSummaryData();

        return $data ? (int) $data['job']->getJobId() : 0;
    }

    public function formatDuration(?float $seconds): string
    {
        if ($seconds === null) {
            return (string) __('N/A');
        }
        $seconds = (int) round($seconds);
        $minutes = intdiv($seconds, 60);
        $remain = $seconds % 60;
        if ($minutes > 0) {
            return (string) __('%1m %2s', $minutes, $remain);
        }

        return (string) __('%1s', $remain);
    }

    public function canExport(): bool
    {
        return $this->_authorization->isAllowed('Dcw_ProductDataValidation::export');
    }

    public function getExportUrl(): string
    {
        return $this->getUrl('product_data_validation/report/export', [
            'job_id' => $this->getJobId(),
        ]);
    }
}
