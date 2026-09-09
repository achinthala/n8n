<?php

declare(strict_types=1);

namespace Dcw\SplitPayment\Observer;

use Dcw\SplitPayment\Model\PaymentMethod;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Admin-created orders using Split Payment are placed On Hold (internal fulfillment flow).
 */
class SetHoldStatusOnAdminOrder implements ObserverInterface
{
    public function __construct(
        private readonly AppState $appState
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->isAdminArea()) {
            return;
        }

        /** @var Order|null $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getPayment()) {
            return;
        }

        if ($order->getPayment()->getMethod() !== PaymentMethod::METHOD_CODE) {
            return;
        }

        $order->hold();
        $order->save();
    }

    private function isAdminArea(): bool
    {
        try {
            return $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
