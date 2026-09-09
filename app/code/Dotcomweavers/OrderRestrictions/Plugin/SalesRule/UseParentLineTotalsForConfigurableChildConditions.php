<?php
declare(strict_types=1);

namespace Dotcomweavers\OrderRestrictions\Plugin\SalesRule;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule\Condition\Product;

/**
 * Configurable child quote rows keep catalog data on the simple SKU (e.g. attribute set "Samples")
 * but row totals are stored on the parent. Core conditions still evaluate cart line totals on the
 * child (0), so rules like "Samples AND row total 0" match every configurable using that simple.
 *
 * For cart-line attributes only, substitute parent totals while evaluating the child so the line
 * behaves like the customer-facing line. Product/catalog attributes still read from the child.
 */
class UseParentLineTotalsForConfigurableChildConditions
{
    private const CART_LINE_PRICE_ATTRIBUTES = [
        'quote_item_row_total',
        'quote_item_price',
    ];

    /**
     * @param Product $subject
     * @param callable $proceed
     * @param AbstractModel $model
     * @return bool
     */
    public function aroundValidate(Product $subject, callable $proceed, AbstractModel $model)
    {
        if (!$this->shouldUseParentCartLineTotals($subject, $model)) {
            return $proceed($model);
        }

        /** @var AbstractItem $model */
        $parent = $model->getParentItem();
        $originalBaseRowTotal = $model->getData('base_row_total');
        $originalPrice = $model->getData('price');

        try {
            $model->setData('base_row_total', $parent->getBaseRowTotal());
            $model->setData('price', $parent->getPrice());

            return $proceed($model);
        } finally {
            $model->setData('base_row_total', $originalBaseRowTotal);
            $model->setData('price', $originalPrice);
        }
    }

    private function shouldUseParentCartLineTotals(Product $subject, AbstractModel $model): bool
    {
        if (!in_array($subject->getAttribute(), self::CART_LINE_PRICE_ATTRIBUTES, true)) {
            return false;
        }
        if (!$model instanceof AbstractItem) {
            return false;
        }
        $parent = $model->getParentItem();
        if ($parent === null) {
            return false;
        }
        if ($parent->getProductType() !== Configurable::TYPE_CODE) {
            return false;
        }

        return (float) $model->getBaseRowTotal() === 0.0;
    }
}
