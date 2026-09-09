<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Quote;

use Dcw\OrderPendingReview\Model\Quote\IncompleteShippingAddressBackfiller;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Runs after payment-information has saved billing, before Magento validates shipping on place order.
 */
class BackfillShippingAddressBeforePlaceOrderPlugin
{
    public function __construct(
        private readonly IncompleteShippingAddressBackfiller $backfiller
    ) {
    }

    /**
     * @param mixed $cartId Quote entity id
     * @return array{0: mixed, 1: PaymentInterface|null}
     */
    public function beforePlaceOrder(
        CartManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod = null
    ): array {
        $this->backfiller->applyToCartId((int) $cartId);

        return [$cartId, $paymentMethod];
    }
}
