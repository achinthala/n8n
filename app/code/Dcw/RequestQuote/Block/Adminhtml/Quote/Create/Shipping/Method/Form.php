<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Block\Adminhtml\Quote\Create\Shipping\Method;

use Dcw\RequestQuote\Service\AdminQuotePermissionService;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;

class Form extends \Amasty\RequestQuote\Block\Adminhtml\Quote\Create\Shipping\Method\Form
{
    /**
     * @var AdminQuotePermissionService
     */
    protected $permissionService;

    /**
     * @var RequestQuoteViewModel
     */
    protected $requestQuoteViewModel;

    /**
     * Override template to use our custom template
     */
    protected $_template = 'Dcw_RequestQuote::quote/create/shipping/method/form.phtml';

    /**
     * @param \Amasty\RequestQuote\Model\Quote\Backend\Edit $quoteEditModel
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Amasty\RequestQuote\Model\Quote\Backend\Session $sessionQuote
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Tax\Helper\Data $taxData
     * @param AdminQuotePermissionService $permissionService
     * @param RequestQuoteViewModel $requestQuoteViewModel
     * @param array $data
     */
    public function __construct(
        \Amasty\RequestQuote\Model\Quote\Backend\Edit $quoteEditModel,
        \Magento\Backend\Block\Template\Context $context,
        \Amasty\RequestQuote\Model\Quote\Backend\Session $sessionQuote,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Tax\Helper\Data $taxData,
        AdminQuotePermissionService $permissionService,
        RequestQuoteViewModel $requestQuoteViewModel,
        array $data = []
    ) {
        $this->permissionService = $permissionService;
        $this->requestQuoteViewModel = $requestQuoteViewModel;
        parent::__construct($quoteEditModel, $context, $sessionQuote, $priceCurrency, $taxData, $data);
    }

    /**
     * Check if admin can override shipping
     *
     * @return bool
     */
    public function canOverrideShipping(): bool
    {
        return $this->permissionService->canOverrideShipping();
    }

    /**
     * Check if quote is locked by customer
     *
     * @return bool
     */
    public function isLockedByCustomer(): bool
    {
        $quote = $this->getQuote();
        $magentoQuoteId = $quote && $quote->getId() ? (int)$quote->getId() : null;
        return $this->requestQuoteViewModel->isLockedByCustomer($magentoQuoteId);
    }
}

