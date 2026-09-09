<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Plugin\Model;

use Dcw\PurchaseOrderReview\Helper\Config;
use Dcw\PurchaseOrderReview\Model\PaymentMethod;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Subtotal threshold + optional guest PO rule for dcw_po_gateway.
 */
class PaymentMethodPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly CustomerSession $customerSession
    ) {
    }

    /**
     * @param CartInterface|null $quote
     */
    public function aroundIsAvailable(PaymentMethod $subject, callable $proceed, $quote = null): bool
    {
        $storeId = $quote ? (int) $quote->getStore()->getId() : null;

        if (!$this->config->isModuleEnabled($storeId)) {
            return (bool) $proceed($quote);
        }

        if (!$subject->isActive($storeId)) {
            return false;
        }

        if (!$quote instanceof CartInterface) {
            return false;
        }

        $threshold = $this->config->getSubtotalThreshold($storeId);
        if ((float) $quote->getBaseSubtotal() < $threshold) {
            return false;
        }

        $result = (bool) $proceed($quote);
        if ($result) {
            return true;
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $this->config->isAllowGuestPo($storeId);
        }

        return true;
    }
}
