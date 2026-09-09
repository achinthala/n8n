<?php

declare(strict_types=1);

namespace Dcw\PaymentMethods\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface;

/**
 * Invoices cannot be registered during {@see sales_order_payment_place_end}: the order is
 * not persisted yet (no entity_id). After {@see \Magento\Sales\Model\Service\OrderService::place}
 * the order is saved; {@see sales_model_service_quote_submit_success} runs next and is safe
 * for offline capture / invoice creation.
 */
class CreateCheckmoOfflineInvoiceAfterQuoteSubmit implements ObserverInterface
{
    private const CHECKMO = 'checkmo';

    public function execute(Observer $observer): void
    {
        /** @var Order|null $order */
        $order = $observer->getEvent()->getData('order');
        if (!$order instanceof Order || !$order->getEntityId()) {
            return;
        }

        $om = ObjectManager::getInstance();
        $orderId = (int) $order->getEntityId();
        $order = $om->get(OrderRepositoryInterface::class)->get($orderId);
        if (!$order instanceof Order) {
            return;
        }

        if (!$order->getPayment() || $order->getPayment()->getMethod() !== self::CHECKMO) {
            return;
        }

        if ($order->getState() !== Order::STATE_PROCESSING) {
            return;
        }

        if (!$order->canInvoice() || $order->hasInvoices()) {
            return;
        }

        $transactionFactory = $om->get(TransactionFactory::class);
        $logger = $om->get(LoggerInterface::class);

        try {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $transaction = $transactionFactory->create();
            $transaction->addObject($invoice)->addObject($invoice->getOrder());
            $transaction->save();
        } catch (\Throwable $e) {
            $logger->error(
                'Dcw_PaymentMethods: Could not auto-create invoice for Check/Money Order: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
