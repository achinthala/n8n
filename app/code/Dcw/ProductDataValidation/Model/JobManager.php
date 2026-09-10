<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

use Dcw\ProductDataValidation\Model\ResourceModel\Job as JobResource;
use Dcw\ProductDataValidation\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Creates and loads validation jobs. Does not auto-create jobs.
 */
class JobManager
{
    public function __construct(
        private readonly JobFactory $jobFactory,
        private readonly JobResource $jobResource,
        private readonly JobCollectionFactory $jobCollectionFactory,
        private readonly Config $config,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Queue a new validation job from Admin action.
     *
     * @throws LocalizedException
     */
    public function createPendingJob(?int $adminUserId): Job
    {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('Product Data Validation is disabled in configuration.'));
        }

        if ($this->config->getRequiredAttributes() === []) {
            throw new LocalizedException(
                __('Please select at least one required attribute in Stores → Configuration → Product Data Validation.')
            );
        }

        if ($this->hasActiveJob()) {
            throw new LocalizedException(
                __('A validation job is already pending or running. Please wait for it to finish.')
            );
        }

        $job = $this->jobFactory->create();
        $job->setStatus(JobStatus::PENDING);
        $job->setData('created_by', $adminUserId);
        $job->setData('store_ids', implode(',', $this->config->getStoreIds()));
        $job->setData('total_eligible', 0);
        $job->setData('products_checked', 0);
        $job->setData('products_with_issues', 0);
        $job->setData('products_passed', 0);
        $this->jobResource->save($job);

        $this->logger->info('Validation job queued', ['job_id' => $job->getJobId()]);

        return $job;
    }

    public function hasActiveJob(): bool
    {
        $collection = $this->jobCollectionFactory->create();
        $collection->addFieldToFilter(
            'status',
            ['in' => [JobStatus::PENDING, JobStatus::RUNNING]]
        );
        $collection->setPageSize(1);

        return (int) $collection->getSize() > 0;
    }

    public function getNextPendingJob(): ?Job
    {
        $collection = $this->jobCollectionFactory->create();
        $collection->addFieldToFilter('status', JobStatus::PENDING);
        $collection->setOrder('job_id', 'ASC');
        $collection->setPageSize(1);
        $job = $collection->getFirstItem();

        return $job && $job->getJobId() ? $job : null;
    }

    public function hasRunningJob(): bool
    {
        $collection = $this->jobCollectionFactory->create();
        $collection->addFieldToFilter('status', JobStatus::RUNNING);
        $collection->setPageSize(1);

        return (int) $collection->getSize() > 0;
    }

    public function getJob(int $jobId): Job
    {
        $job = $this->jobFactory->create();
        $this->jobResource->load($job, $jobId);
        if (!$job->getJobId()) {
            throw new LocalizedException(__('Validation job #%1 was not found.', $jobId));
        }

        return $job;
    }

    public function markRunning(Job $job): void
    {
        $job->setStatus(JobStatus::RUNNING);
        $job->setData('started_at', $this->dateTime->gmtDate());
        $this->jobResource->save($job);
    }

    public function markCompleted(Job $job): void
    {
        $job->setStatus(JobStatus::COMPLETED);
        $job->setData('completed_at', $this->dateTime->gmtDate());
        $this->jobResource->save($job);
    }

    public function markFailed(Job $job, string $safeMessage): void
    {
        $job->setStatus(JobStatus::FAILED);
        $job->setData('completed_at', $this->dateTime->gmtDate());
        $job->setData('error_message', $safeMessage);
        $this->jobResource->save($job);
    }

    public function save(Job $job): void
    {
        $this->jobResource->save($job);
    }

    /**
     * Latest completed job ID, or 0 when none exist.
     */
    public function getLatestCompletedJobId(): int
    {
        $collection = $this->jobCollectionFactory->create();
        $collection->addFieldToFilter('status', JobStatus::COMPLETED);
        $collection->setOrder('job_id', 'DESC');
        $collection->setPageSize(1);
        $job = $collection->getFirstItem();

        return $job && $job->getJobId() ? (int) $job->getJobId() : 0;
    }
}
