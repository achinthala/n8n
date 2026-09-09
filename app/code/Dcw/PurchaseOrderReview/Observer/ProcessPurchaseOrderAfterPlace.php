<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Observer;

use Dcw\PurchaseOrderReview\Helper\Config;
use Dcw\PurchaseOrderReview\Model\Email\PoEmailSender;
use Dcw\PurchaseOrderReview\Model\Order\AddressFormatter;
use Dcw\PurchaseOrderReview\Model\Order\PendingReviewPlacementState;
use Dcw\PurchaseOrderReview\Model\PaymentMethod;
use Dcw\PurchaseOrderReview\Model\ZendeskTicket;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class ProcessPurchaseOrderAfterPlace implements ObserverInterface
{
    private const INFO_KEY = 'dcw_po_review_processed';

    public function __construct(
        private readonly Config $config,
        private readonly PoEmailSender $poEmailSender,
        private readonly ZendeskTicket $zendeskTicket,
        private readonly AddressFormatter $addressFormatter,
        private readonly CompanyManagementInterface $companyManagement,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getPayment()) {
            return;
        }

        if ($order->getPayment()->getMethod() !== PaymentMethod::METHOD_CODE) {
            return;
        }

        $storeId = (int) $order->getStoreId();
        if (!$this->config->isModuleEnabled($storeId)) {
            return;
        }

        if ($order->getPayment()->getAdditionalInformation(self::INFO_KEY)) {
            return;
        }

        try {
            $priorState = (string) $order->getState();
            $order->setState(PendingReviewPlacementState::resolveAfterPlace($priorState))->setStatus('po_pending_review');

            $this->poEmailSender->sendCustomerExpectation($order);

            $ticketBody = $this->buildTicketBody($order);
            $subject = sprintf('Pending PO Review — Order #%s', $order->getIncrementId());
            $requesterEmail = (string) $order->getCustomerEmail();
            $requesterName = trim((string) $order->getCustomerName());

            $zendeskResult = $this->zendeskTicket->create(
                $storeId,
                $subject,
                $ticketBody,
                $requesterEmail,
                $requesterName
            );

            $ticketId = null;
            if (is_array($zendeskResult['body'] ?? null)
                && isset($zendeskResult['body']['ticket']['id'])
            ) {
                $ticketId = (int) $zendeskResult['body']['ticket']['id'];
            }

            if ($zendeskResult['success'] && $ticketId) {
                $order->getPayment()->setAdditionalInformation('zendesk_ticket_id', $ticketId);
            } else {
                $fallbackTo = $this->config->getFallbackEmail($storeId);
                if ($fallbackTo !== '') {
                    $this->poEmailSender->sendFallbackPendingReview(
                        $order,
                        $fallbackTo,
                        nl2br($this->escapeHtmlForEmail($ticketBody))
                    );
                    $order->getPayment()->setAdditionalInformation('zendesk_fallback_email_sent', 1);
                }
            }

            $order->getPayment()->setAdditionalInformation(self::INFO_KEY, 1);
            $order->save();
        } catch (\Throwable $e) {
            $this->logger->error('Dcw_PurchaseOrderReview place after error: ' . $e->getMessage());
        }
    }

    private function buildTicketBody(Order $order): string
    {
        $companyName = 'N/A';
        if ($order->getCustomerId()) {
            try {
                $company = $this->companyManagement->getByCustomerId((int) $order->getCustomerId());
                if ($company !== null) {
                    $companyName = (string) $company->getCompanyName();
                }
            } catch (\Throwable $e) {
                $companyName = 'N/A';
            }
        }

        $billing = $order->getBillingAddress()
            ? $this->addressFormatter->format($order->getBillingAddress())
            : 'N/A';
        $shipping = $order->getShippingAddress()
            ? $this->addressFormatter->format($order->getShippingAddress())
            : 'N/A';

        $payment = $order->getPayment();
        $confirmName = trim((string) $payment->getAdditionalInformation(PaymentMethod::INFO_CONTACT_NAME));
        $confirmPhone = trim((string) $payment->getAdditionalInformation(PaymentMethod::INFO_CONTACT_PHONE));
        $confirmEmail = trim((string) $payment->getAdditionalInformation(PaymentMethod::INFO_CONTACT_EMAIL));
        $contactBlock = '';
        if ($confirmName !== '' || $confirmPhone !== '' || $confirmEmail !== '') {
            $contactBlock = sprintf(
                "\n\nContact details (confirmed at checkout):\nName: %s\nPhone: %s\nEmail: %s",
                $confirmName !== '' ? $confirmName : '—',
                $confirmPhone !== '' ? $confirmPhone : '—',
                $confirmEmail !== '' ? $confirmEmail : '—'
            );
        }

        return sprintf(
            "Order #: %s\n" .
            "Customer: %s\n" .
            "Company: %s\n" .
            "Total: %s\n\n" .
            "Billing:\n%s\n\n" .
            "Shipping:\n%s%s",
            $order->getIncrementId(),
            trim((string) $order->getCustomerName()),
            $companyName,
            $order->formatPriceTxt((float) $order->getGrandTotal()),
            $billing,
            $shipping,
            $contactBlock
        );
    }

    private function escapeHtmlForEmail(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
