<?php
declare(strict_types=1);

namespace Dotcomweavers\OrderRestrictions\Model\Rule\Condition\Product;

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule\Condition\Product\Found as SalesRuleProductFound;

/**
 * "Product attribute combination" uses visible quote lines as the outer loop (customer-facing rows).
 *
 * Inner conditions still expand configurable parents to children via Product\Combine; cart line
 * totals on those children are normalized in
 * {@see \Dotcomweavers\OrderRestrictions\Plugin\SalesRule\UseParentLineTotalsForConfigurableChildConditions}
 * so "attribute set + row total" matches the paid line, not the zero-priced child row alone.
 */
class Found extends SalesRuleProductFound
{
    /**
     * @inheritdoc
     */
    public function validate(AbstractModel $model)
    {
        $items = $this->getVisibleQuoteItems($model);
        if ($items === null) {
            return parent::validate($model);
        }

        $isValid = false;
        foreach ($items as $item) {
            if ($this->validateProductCombineConditions($item)) {
                $isValid = true;
                break;
            }
        }

        return $isValid;
    }

    /**
     * Same as core Found inner loop: evaluate nested conditions for one quote item (not Address).
     *
     * @param AbstractModel $quoteItem
     * @return bool
     */
    private function validateProductCombineConditions(AbstractModel $quoteItem): bool
    {
        return $this->_isValid($quoteItem);
    }

    /**
     * @param AbstractModel $model Typically quote Address during rule validation.
     * @return \Magento\Quote\Model\Quote\Item\AbstractItem[]|null
     */
    private function getVisibleQuoteItems(AbstractModel $model): ?array
    {
        if ($model instanceof Address) {
            return $model->getAllVisibleItems();
        }
        if ($model instanceof Quote) {
            return $model->getAllVisibleItems();
        }

        return null;
    }
}
