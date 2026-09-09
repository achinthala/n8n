<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Model\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class PoEmailSender
{
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function sendCustomerExpectation(OrderInterface $order): void
    {
        $storeId = (int) $order->getStoreId();
        $this->transportBuilder
            ->setTemplateIdentifier('dcw_po_review_expectation')
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars(['order' => $order, 'store' => $this->storeManager->getStore($storeId)])
            ->setFromByScope('general', $storeId)
            ->addTo((string) $order->getCustomerEmail(), (string) $order->getCustomerName())
            ->getTransport()
            ->sendMessage();
    }

    public function sendFallbackPendingReview(OrderInterface $order, string $toEmail, string $bodyHtml): void
    {
        $storeId = (int) $order->getStoreId();
        $this->transportBuilder
            ->setTemplateIdentifier('dcw_po_review_fallback')
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars([
                'order' => $order,
                'store' => $this->storeManager->getStore($storeId),
                'body_html' => $bodyHtml,
            ])
            ->setFromByScope('general', $storeId)
            ->addTo($toEmail)
            ->getTransport()
            ->sendMessage();
    }
}
