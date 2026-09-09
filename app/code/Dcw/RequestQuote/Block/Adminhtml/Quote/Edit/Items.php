<?php

namespace Dcw\RequestQuote\Block\Adminhtml\Quote\Edit;

use Dcw\RequestQuote\Service\AdminQuotePermissionService;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;

class Items extends \Amasty\RequestQuote\Block\Adminhtml\Quote\Edit\Items
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
     * Get buttons HTML, filtering out add products button if user doesn't have permission or quote is locked
     *
     * @return string
     */
    public function getButtonsHtml()
    {
        // Filter buttons based on permission and lock status
        $isLocked = $this->isLockedByCustomer();
        $hasPermission = $this->canAddOrConfigureProducts();
        
        // Filter out "Add products" button if user doesn't have permission (Quantity Edits & Remove Items) or quote is locked
        // canAddOrConfigureProducts() requires both canEditQuantity() AND canRemoveItems()
        // Both check the same permission: quantity_edit_roles
        if (!$hasPermission || $isLocked) {
            if (is_array($this->_buttons) && !empty($this->_buttons)) {
                $filteredButtons = [];
                foreach ($this->_buttons as $key => $buttonData) {
                    if (!is_array($buttonData)) {
                        $filteredButtons[$key] = $buttonData;
                        continue;
                    }
                    
                    $label = $buttonData['label'] ?? '';
                    $onclick = $buttonData['onclick'] ?? '';
                    
                    if (is_object($label) && method_exists($label, '__toString')) {
                        $label = (string)$label;
                    }
                    $label = (string)$label;
                    
                    $labelLower = strtolower($label);
                    $onclickLower = strtolower((string)$onclick);
                    
                    $isAddProductButton = (
                        (stripos($labelLower, 'add') !== false && (stripos($labelLower, 'product') !== false || stripos($labelLower, 'item') !== false)) ||
                        (stripos($onclickLower, 'add') !== false && (stripos($onclickLower, 'product') !== false || stripos($onclickLower, 'show') !== false)) ||
                        (stripos($onclickLower, 'show') !== false && stripos($onclickLower, 'product') !== false) ||
                        $labelLower === 'add products' ||
                        $labelLower === 'add product'
                    );
                    
                    if (!$isAddProductButton) {
                        $filteredButtons[$key] = $buttonData;
                    }
                }
                $this->_buttons = $filteredButtons;
            }
        }
        
        // Use parent's logic but with filtered buttons
        $html = '';
        // Make buttons to be rendered in opposite order of addition. This makes "Add products" the last one.
        $this->_buttons = array_reverse($this->_buttons);
        foreach ($this->_buttons as $buttonData) {
            $html .= $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Button::class
            )->setData(
                $buttonData
            )->toHtml();
        }

        return $html;
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
     * Check if quote is locked by customer
     *
     * @return bool
     */
    public function isLockedByCustomer(): bool
    {
        // First try to get Amasty quote ID from request (most reliable)
        $requestQuoteId = (int)$this->getRequest()->getParam('quote_id');
        if ($requestQuoteId) {
            // Request parameter is the Amasty quote ID, pass null to let ViewModel use it
            return $this->requestQuoteViewModel->isLockedByCustomer(null);
        }
        
        // Fallback: get from quote object and convert
        $quote = $this->getQuote();
        $magentoQuoteId = $quote && $quote->getId() ? (int)$quote->getId() : null;
        return $this->requestQuoteViewModel->isLockedByCustomer($magentoQuoteId);
    }
}

