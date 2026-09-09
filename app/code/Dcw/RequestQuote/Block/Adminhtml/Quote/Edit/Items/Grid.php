<?php

namespace Dcw\RequestQuote\Block\Adminhtml\Quote\Edit\Items;

use Dcw\RequestQuote\Service\AdminQuotePermissionService;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;

class Grid extends \Amasty\RequestQuote\Block\Adminhtml\Quote\Edit\Items\Grid
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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Amasty\RequestQuote\Model\Quote\Backend\Session $sessionQuote
     * @param \Amasty\RequestQuote\Model\Quote\Backend\Edit $orderCreate
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     * @param \Amasty\Base\Model\Serializer $serializer
     * @param \Amasty\RequestQuote\Helper\Data $configHelper
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param AdminQuotePermissionService $permissionService
     * @param RequestQuoteViewModel $requestQuoteViewModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Amasty\RequestQuote\Model\Quote\Backend\Session $sessionQuote,
        \Amasty\RequestQuote\Model\Quote\Backend\Edit $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Amasty\Base\Model\Serializer $serializer,
        \Amasty\RequestQuote\Helper\Data $configHelper,
        \Magento\Catalog\Helper\Data $catalogHelper,
        AdminQuotePermissionService $permissionService,
        RequestQuoteViewModel $requestQuoteViewModel,
        array $data = []
    ) {
        $this->permissionService = $permissionService;
        $this->requestQuoteViewModel = $requestQuoteViewModel;
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $wishlistFactory,
            $taxConfig,
            $taxData,
            $stockRegistry,
            $stockState,
            $serializer,
            $configHelper,
            $catalogHelper,
            $data
        );
    }

    /**
     * @param Item $item
     * @return float
     */
    public function getItemEditablePrice($item)
    {
        // Retrieve the quote
        $quote = $this->getQuote();

        // Find the corresponding item in the quote
        $quoteItem = $quote->getItemById($item->getItemId());

        // Return the base price from the quote item, or fallback if not found
        return $quoteItem ? $quoteItem->getBasePrice() : 0;
    }

    /**
     * Check if admin can edit quantities
     *
     * @return bool
     */
    public function canEditQuantity(): bool
    {
        return $this->permissionService->canEditQuantity();
    }

    /**
     * Check if admin can remove items
     *
     * @return bool
     */
    public function canRemoveItems(): bool
    {
        return $this->permissionService->canRemoveItems();
    }

    /**
     * Check if admin can edit item prices
     *
     * @return bool
     */
    public function canEditItemPrices(): bool
    {
        return $this->permissionService->canEditItemPrices();
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
     * Check if admin can control pricing (complete cart control)
     *
     * @return bool
     */
    public function canControlPricing(): bool
    {
        return $this->permissionService->canControlPricing();
    }

    /**
     * Check if admin can add or configure products
     * Allowed if: quantity edit AND remove items
     *
     * @return bool
     */
    public function canAddOrConfigureProducts(): bool
    {
        return $this->permissionService->canEditQuantity() && $this->permissionService->canRemoveItems();
    }

    /**
     * Get lock status URL
     *
     * @return string
     */
    public function getLockStatusUrl(): string
    {
        $quoteId = (int)$this->getRequest()->getParam('quote_id');
        return $this->getUrl('dcwrequestquote/quote_lock/status', ['quote_id' => $quoteId]);
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