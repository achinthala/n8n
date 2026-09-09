<?php

namespace WeltPixel\GA4\Block\Adminhtml\System\Config;

/**
 * Class SeparatorElement
 * @package WeltPixel\GA4\Block\Adminhtml\System\Config
 */
class ImportantNote extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<div>' .
            "<p class='importany-note'>If you're upgrading to version 1.12.1 or newer from a previous version, in order to keep collecting data, regenerate and reimport the JSON file into your Google Tag Manager container, ensuring you choose the Merge + Overwrite Conflicting Tags, Triggers and Variables option when prompted. Detailed steps for the process can be found in the following article: <a target='_blank' href='https://support.weltpixel.com/hc/en-us/articles/11489725090962-Reimporting-a-Google-Tag[…]erge-and-Overwrite-Conflicting-Tags-Triggers-and-Variables'>Reimporting a Google Tag Manager Container</a>. This is required because various dataLayer properties have been restructured.
<br>
<br>
If you're using a fresh installation, follow the normal configuration instructions in the <a target='_blank' href='https://weltpixel.com/resources/ModuleDoc/Magento2/GA4/User-Guide-WeltPixel-Google-Analytics-4.html'>User Guide</a>.</p>" .
            '</div>';

        return $html;
    }
}
