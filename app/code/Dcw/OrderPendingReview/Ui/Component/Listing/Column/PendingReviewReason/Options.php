<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Ui\Component\Listing\Column\PendingReviewReason;

use Dcw\OrderPendingReview\Model\OrderRestriction\PendingReviewRuleOptionsProvider;
use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    /** @var list<array{value: string, label: string}>|null */
    private ?array $options = null;

    public function __construct(
        private readonly PendingReviewRuleOptionsProvider $pendingReviewRuleOptionsProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = $this->pendingReviewRuleOptionsProvider->toOptionArray();
        }

        return $this->options;
    }
}
