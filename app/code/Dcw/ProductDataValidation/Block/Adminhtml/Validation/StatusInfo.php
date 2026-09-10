<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Block\Adminhtml\Validation;

use Dcw\ProductDataValidation\Model\Job;
use Dcw\ProductDataValidation\Model\JobStatus;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

/**
 * Job status detail panel.
 */
class StatusInfo extends Template
{
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getJob(): ?Job
    {
        $job = $this->registry->registry('dcw_pdv_current_job');

        return $job instanceof Job ? $job : null;
    }

    public function getRefreshUrl(): string
    {
        $job = $this->getJob();

        return $this->getUrl('product_data_validation/validation/status', [
            'job_id' => $job?->getJobId(),
        ]);
    }

    public function getReportUrl(): string
    {
        $job = $this->getJob();

        return $this->getUrl('product_data_validation/report/index', [
            'job_id' => $job?->getJobId(),
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->getJob()?->getStatus() === JobStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->getJob()?->getStatus() === JobStatus::FAILED;
    }

    public function getProgressLabel(): string
    {
        $job = $this->getJob();
        if (!$job) {
            return '';
        }
        $checked = (int) $job->getData('products_checked');
        $total = (int) $job->getData('total_eligible');

        return sprintf('%s / %s', number_format($checked), number_format($total));
    }
}
