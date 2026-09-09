<?php
/**
 * TEST FILE - Created for testing BccEmailExtension - Safe to delete
 * Block class for the email test form
 */

namespace Incstores\BccEmailExtension\Block\Adminhtml\Test;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

class Form extends Template
{
    protected $formKey;

    public function __construct(
        Context $context,
        FormKey $formKey,
        array $data = []
    ) {
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('bccemailtest/test/send');
    }
}
