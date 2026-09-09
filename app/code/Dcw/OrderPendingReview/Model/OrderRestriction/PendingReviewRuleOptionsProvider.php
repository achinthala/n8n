<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\OrderRestriction;

use Dotcomweavers\OrderRestrictions\Model\ResourceModel\Rule\CollectionFactory as RulesCollectionFactory;

/**
 * Pending-review Order Restriction rules as UI options (admin grid filter, order view).
 */
class PendingReviewRuleOptionsProvider
{
    public function __construct(
        private readonly RulesCollectionFactory $rulesCollectionFactory
    ) {
    }

    /**
     * Options for grid filter / multiselect (value = restriction_id).
     *
     * @return list<array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $collection = $this->rulesCollectionFactory->create();
        $collection->addFieldToFilter('enabled', 1);
        $collection->addFieldToFilter('apply_type', 'pending_review');
        $collection->setOrder('name', 'ASC');

        $options = [];
        foreach ($collection->getItems() as $rule) {
            $id = (int) $rule->getId();
            if ($id <= 0) {
                continue;
            }
            $name = trim((string) $rule->getName());
            $label = $name !== '' ? $name : (string) __('Rule #%1', $id);
            $options[] = [
                'value' => (string) $id,
                'label' => $label . ' (#' . $id . ')',
            ];
        }

        return $options;
    }
}
