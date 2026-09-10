<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model\Config\Source;

use Dcw\ProductDataValidation\Model\JobStatus;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Job status filter options.
 */
class JobStatusOptions implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => JobStatus::PENDING, 'label' => __('Pending')],
            ['value' => JobStatus::RUNNING, 'label' => __('Running')],
            ['value' => JobStatus::COMPLETED, 'label' => __('Completed')],
            ['value' => JobStatus::FAILED, 'label' => __('Failed')],
        ];
    }
}
