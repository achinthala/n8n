<?php

namespace WeltPixel\GA4\Block\Adminhtml\System\Config;

/**
 * Class SeparatorElement
 * @package WeltPixel\GA4\Block\Adminhtml\System\Config
 */
class SeparatorElement extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $linkMessage = '';

        if (isset($originalData['button_url'])) {
            $linkMessage = '<p style="padding-top: 20px;">For more details about how to use Custom Dimensions, check out this Support Center article: <a href="' . $originalData['button_url'] . '" target="_blank">Google Analytics 4 - Using Custom Dimensions</a></p>';
        }

        $html = '
            <div class="message" style="text-align: center; margin-top: 20px;">
                <strong>' . $originalData['button_label']  . '</strong><br />
            </div>
        ' . $linkMessage;

        return $html;
    }
}
