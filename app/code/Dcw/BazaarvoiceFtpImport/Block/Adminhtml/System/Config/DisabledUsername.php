<?php

/**
 * Disabled Username Field
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class DisabledUsername extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Get the value from our module's username configuration
        $value = $this->scopeConfig->getValue(
            'bazaarvoice_ftp/ftp_settings/username',
            'default',
            0
        );

        // Set the value and make it read-only
        $element->setValue($value);
        $element->setReadonly(true);
        $element->setClass('readonly');
        $element->setDisabled(true);

        return parent::_getElementHtml($element);
    }
}
