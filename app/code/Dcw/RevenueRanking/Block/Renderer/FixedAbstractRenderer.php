<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Block\Renderer;

use Magento\Framework\View\Element\Template;
use Mirasvit\LayeredNavigation\Block\Renderer\AbstractRenderer;

class FixedAbstractRenderer extends AbstractRenderer
{
    /**
     * Get rel attribute value with proper fallback
     *
     * @return string
     */
    public function getRelAttributeValue(): string
    {
        try {
            $relValue = $this->seoConfigProvider->getRelAttribute();
            
            // Check if the value is valid and not undefined
            if (empty($relValue) || $relValue === 'undefined' || $relValue === null) {
                return '';
            }
            
            return (string) $relValue;
        } catch (\Exception $e) {
            // If there's any error getting the rel attribute, return empty string
            return '';
        }
    }
    
    /**
     * Get rel attribute HTML with proper escaping
     *
     * @return string
     */
    public function getRelAttributeHtml(): string
    {
        $relValue = $this->getRelAttributeValue();
        
        if (empty($relValue)) {
            return '';
        }
        
        return 'rel="' . $this->escapeHtmlAttr($relValue) . '"';
    }
}
