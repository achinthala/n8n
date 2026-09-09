<?php
declare(strict_types=1);

/**
 * Copyright © Incstores. All rights reserved.
 */

namespace Incstores\QuoteExtensions\Plugin\Quote;

use Amasty\RequestQuote\Block\Adminhtml\Quote\View\Items\Renderer\DefaultRenderer;
use Magento\Framework\DataObject;
use Magento\Catalog\Helper\Product\Configuration;

/**
 * Plugin to add Weight column to Amasty quote items
 */
class AddWeightColumn
{
    /**
     * @var Configuration
     */
    private $productConfig;

    /**
     * @param Configuration $productConfig
     */
    public function __construct(
        Configuration $productConfig
    ) {
        $this->productConfig = $productConfig;
    }
    /**
     * Add custom column rendering for quote items
     *
     * @param DefaultRenderer $subject
     * @param callable $proceed
     * @param DataObject $item
     * @param string $column
     * @param mixed $field
     * @return string
     */
    public function aroundGetColumnHtml(
        DefaultRenderer $subject,
        callable $proceed,
        DataObject $item,
        string $column,
        $field = null
    ) {
        if ($column === 'row-weight') {
            return $this->getRowWeight($item);
        }

        return $proceed($item, $column, $field);
    }

    /**
     * Get formatted row weight with custom length calculation
     *
     * @param DataObject $item
     * @return string
     */
    private function getRowWeight(DataObject $item): string
    {
        $unitWeight = $item->getWeight();
        $qty = $item->getQty();

        // Check if Custom Length exists in order options
        $customLength = $this->getCustomLength($item);

        // Calculate total weight based on Custom Length rules
        if ($customLength !== null && $customLength > 0) {
            // weight = (Custom Length * line item weight) * Qty
            $totalWeight = ($customLength * $unitWeight) * $qty;
        } else {
            // weight = line item weight * qty
            $totalWeight = $unitWeight * $qty;
        }

        if ($totalWeight === null || $totalWeight === '' || $totalWeight == 0) {
            return '-';
        }

        return number_format((float)$totalWeight, 2) . ' lbs';
    }

    /**
     * Get Custom Length from item's additional data or order options
     *
     * @param DataObject $item
     * @return float|null
     */
    private function getCustomLength(DataObject $item): ?float
    {
        // Try to get from additional_data first (for quote items)
        $additionalData = $item->getAdditionalData();
        if ($additionalData) {
            try {
                $data = json_decode($additionalData, true);
                if (isset($data['CustomLength']) && is_numeric($data['CustomLength'])) {
                    return (float)$data['CustomLength'];
                }
            } catch (\Exception $e) {
                // Continue to check order options
            }
        }

        // Use the same method as the template: getCustomOptions() from Product Configuration helper
        // This matches how the template displays Custom Length in the order options section
        $options = $this->productConfig->getCustomOptions($item);
        if ($options) {
            foreach ($options as $option) {
                if (isset($option['label']) && strtolower($option['label']) === 'custom length') {
                    if (isset($option['value']) && is_numeric($option['value'])) {
                        return (float)$option['value'];
                    }
                }
            }
        }

        return null;
    }
}
