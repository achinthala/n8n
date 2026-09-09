<?php

declare(strict_types=1);

namespace Dcw\Checkout\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Adds UI validation to all checkout telephone fields (shipping + billing forms).
 */
class LayoutProcessorPlugin
{
    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        if (!isset($jsLayout['components']['checkout'])) {
            return $jsLayout;
        }

        $this->applyTelephoneValidation($jsLayout['components']['checkout']);

        return $jsLayout;
    }

    private function applyTelephoneValidation(array &$node): void
    {
        foreach ($node as $key => &$value) {
            if ($key === 'telephone' && is_array($value)) {
                if (!isset($value['validation']) || !is_array($value['validation'])) {
                    $value['validation'] = [];
                }
                $value['validation']['dcw-phone-min-digits'] = true;
            }
            if (is_array($value)) {
                $this->applyTelephoneValidation($value);
            }
        }
        unset($value);
    }
}
