<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Block\Adminhtml\Validation;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

/**
 * Header actions for Validation Jobs listing.
 */
class Header extends Template
{
    public function __construct(
        Context $context,
        private readonly FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getStartUrl(): string
    {
        return $this->getUrl('product_data_validation/validation/start');
    }

    public function canRun(): bool
    {
        return $this->_authorization->isAllowed('Dcw_ProductDataValidation::run');
    }
}
