<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Block\Adminhtml\Quote;

use Dcw\RequestQuote\Service\AdminQuotePermissionService;

class View extends \Amasty\RequestQuote\Block\Adminhtml\Quote\View
{
    /**
     * @var AdminQuotePermissionService
     */
    protected $permissionService;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Amasty\RequestQuote\Model\Quote\Backend\Session $quoteSession
     * @param AdminQuotePermissionService $permissionService
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Amasty\RequestQuote\Model\Quote\Backend\Session $quoteSession,
        AdminQuotePermissionService $permissionService,
        array $data = []
    ) {
        $this->permissionService = $permissionService;
        parent::__construct($context, $quoteSession, $data);
    }

    /**
     * Check if admin can edit Admin User Assistance
     *
     * @return bool
     */
    public function canEditAdminAssistance(): bool
    {
        return $this->permissionService->canEditAdminAssistance();
    }
}

