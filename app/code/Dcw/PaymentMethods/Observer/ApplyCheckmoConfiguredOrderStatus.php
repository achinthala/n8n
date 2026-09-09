<?php

declare(strict_types=1);

namespace Dcw\PaymentMethods\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

/**
 * Offline payment place() always starts from {@see Order::STATE_NEW}. If the configured
 * Check / Money Order status (e.g. processing) is not assigned to the "new" state, core
 * replaces it with the default for "new" (typically Pending). Align state + status so
 * the admin-configured value is honored.
 *
 * Offline invoicing runs in {@see CreateCheckmoOfflineInvoiceAfterQuoteSubmit} after the
 * order is saved (see {@see sales_model_service_quote_submit_success}).
 */
class ApplyCheckmoConfiguredOrderStatus implements ObserverInterface
{
    private const CHECKMO = 'checkmo';

    public function execute(Observer $observer): void
    {
        /** @var Payment|null $payment */
        $payment = $observer->getEvent()->getData('payment');
        if (!$payment instanceof Payment) {
            return;
        }

        if ($payment->getMethod() !== self::CHECKMO) {
            return;
        }

        $order = $payment->getOrder();
        if (!$order instanceof Order) {
            return;
        }

        $method = $payment->getMethodInstance();
        $method->setStore((int) $order->getStoreId());
        $configuredStatus = (string) $method->getConfigData('order_status');
        if ($configuredStatus === '') {
            return;
        }

        $orderConfig = $order->getConfig();
        $states = [
            Order::STATE_NEW,
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_PROCESSING,
            Order::STATE_COMPLETE,
            Order::STATE_CLOSED,
            Order::STATE_CANCELED,
            Order::STATE_HOLDED,
            Order::STATE_PAYMENT_REVIEW,
        ];

        foreach ($states as $state) {
            $statuses = $orderConfig->getStateStatuses($state);
            if (!\array_key_exists($configuredStatus, $statuses)) {
                continue;
            }

            if ($order->getState() !== $state || $order->getStatus() !== $configuredStatus) {
                $order->setState($state)->setStatus($configuredStatus);
            }

            return;
        }
    }
}
