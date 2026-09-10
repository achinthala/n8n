<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Cron;

use Dcw\ProductDataValidation\Model\JobManager;
use Dcw\ProductDataValidation\Model\ValidationProcessor;
use Psr\Log\LoggerInterface;

/**
 * Processes Admin-queued validation jobs only. Never creates jobs automatically.
 */
class ProcessValidation
{
    public function __construct(
        private readonly JobManager $jobManager,
        private readonly ValidationProcessor $validationProcessor,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        if ($this->jobManager->hasRunningJob()) {
            $this->logger->info('Skipping validation cron: a job is already running.');
            return;
        }

        $job = $this->jobManager->getNextPendingJob();
        if ($job === null) {
            // No pending Admin-initiated work — intentional no-op.
            return;
        }

        $this->logger->info('Cron picked validation job', ['job_id' => $job->getJobId()]);
        $this->validationProcessor->process($job);
    }
}
