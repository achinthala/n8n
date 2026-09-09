<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Block\Adminhtml\Quote\Edit\Items;

use Dcw\RequestQuote\Service\AdminQuotePermissionService;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;

class Discounts extends \Amasty\RequestQuote\Block\Adminhtml\Quote\Edit\Items\Discounts
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
    protected $_template = 'Dcw_RequestQuote::quote/edit/items/discounts.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Amasty\RequestQuote\Model\Quote\Backend\Session $sessionQuote
     * @param \Amasty\RequestQuote\Model\Quote\Backend\Edit $orderCreate
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param AdminQuotePermissionService $permissionService
     * @param RequestQuoteViewModel $requestQuoteViewModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Amasty\RequestQuote\Model\Quote\Backend\Session $sessionQuote,
        \Amasty\RequestQuote\Model\Quote\Backend\Edit $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        AdminQuotePermissionService $permissionService,
        RequestQuoteViewModel $requestQuoteViewModel,
        array $data = []
    ) {
        $this->permissionService = $permissionService;
        $this->requestQuoteViewModel = $requestQuoteViewModel;
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $data);
    }

    /**
     * Check if admin can apply discounts
     *
     * @return bool
     */
    public function canApplyDiscounts(): bool
    {
        return $this->permissionService->canApplyDiscounts();
    }

    /**
     * Check if admin can apply 100% discount
     *
     * @return bool
     */
    public function canApplyFullDiscount(): bool
    {
        return $this->permissionService->canApplyFullDiscount();
    }

    /**
     * Get quote ID
     *
     * @return int|null
     */
    public function getQuoteId(): ?int
    {
        $quote = $this->getQuote();
        return $quote ? (int)$quote->getId() : null;
    }

    /**
     * Get log discount URL
     *
     * @return string
     */
    public function getLogDiscountUrl(): string
    {
        return $this->getUrl('dcwrequestquote/quote/logDiscount');
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

