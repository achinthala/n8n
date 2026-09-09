<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Model\Email;

use Dcw\ProductEnquiry\Model\Config;
use Dcw\ProductEnquiry\Model\Enquiry;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class EnquiryEmailSender
{
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function sendStoreNotification(Enquiry $enquiry): void
    {
        $storeId = (int) $enquiry->getData('store_id');
        $recipients = $this->config->getRecipientEmails($storeId);

        if ($recipients === []) {
            $this->logger->warning('ProductEnquiry store notification skipped: no recipient configured', [
                'enquiry_id' => $enquiry->getEnquiryId(),
            ]);

            return;
        }

        $store = $this->storeManager->getStore($storeId);
        $transportBuilder = $this->transportBuilder
            ->setTemplateIdentifier('dcw_product_enquiry_notification')
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($this->buildTemplateVars($enquiry, $store))
            ->setFromByScope('general', $storeId);

        foreach ($recipients as $recipient) {
            $transportBuilder->addTo($recipient);
        }

        $transportBuilder->getTransport()->sendMessage();

        $this->logger->info('ProductEnquiry store notification sent', [
            'enquiry_id' => $enquiry->getEnquiryId(),
            'recipients' => $recipients,
        ]);
    }

    public function sendCustomerAcknowledgement(Enquiry $enquiry): void
    {
        $storeId = (int) $enquiry->getData('store_id');

        if (!$this->config->isCustomerAcknowledgementEnabled($storeId)) {
            return;
        }

        $email = trim($enquiry->getEmail());
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $store = $this->storeManager->getStore($storeId);

        $this->transportBuilder
            ->setTemplateIdentifier('dcw_product_enquiry_acknowledgement')
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($this->buildTemplateVars($enquiry, $store))
            ->setFromByScope('general', $storeId)
            ->addTo($email, $enquiry->getName() ?: null)
            ->getTransport()
            ->sendMessage();

        $this->logger->info('ProductEnquiry customer acknowledgement sent', [
            'enquiry_id' => $enquiry->getEnquiryId(),
            'email' => $email,
        ]);
    }

    private function buildTemplateVars(Enquiry $enquiry, $store): array
    {
        return [
            'store' => $store,
            'enquiry' => $enquiry,
            'customer_name' => (string) $enquiry->getData('name'),
            'customer_email' => (string) $enquiry->getData('email'),
            'customer_phone' => (string) $enquiry->getData('phone'),
            'message' => (string) $enquiry->getData('message'),
            'product_name' => (string) $enquiry->getData('product_name'),
            'product_sku' => (string) $enquiry->getData('product_sku'),
            'product_id' => (string) $enquiry->getData('product_id'),
        ];
    }
}
