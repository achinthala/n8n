<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Validation job entity.
 */
class Job extends AbstractModel
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Job::class);
    }

    public function getJobId(): ?int
    {
        $id = $this->getData('job_id');

        return $id !== null ? (int) $id : null;
    }

    public function getStatus(): string
    {
        return (string) $this->getData('status');
    }

    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    /**
     * @return array<string, int>
     */
    public function getAttributeBreakdown(): array
    {
        $raw = $this->getData('attribute_breakdown');
        if (!$raw) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, int> $breakdown
     */
    public function setAttributeBreakdown(array $breakdown): self
    {
        return $this->setData('attribute_breakdown', json_encode($breakdown));
    }
}
