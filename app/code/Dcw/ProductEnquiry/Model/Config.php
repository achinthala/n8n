<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'dcw_product_enquiry/general/enabled';
    private const XML_PATH_RECIPIENT_EMAIL = 'dcw_product_enquiry/general/recipient_email';
    private const XML_PATH_SEND_ACK = 'dcw_product_enquiry/general/send_customer_acknowledgement';
    private const XML_PATH_FORM_TITLE = 'dcw_product_enquiry/general/form_title';
    private const XML_PATH_SUCCESS_MESSAGE = 'dcw_product_enquiry/general/success_message';
    private const XML_PATH_STORE_EMAIL = 'trans_email/ident_general/email';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string[]
     */
    public function getRecipientEmails(?int $storeId = null): array
    {
        $configured = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_RECIPIENT_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($configured === '') {
            $fallback = trim((string) $this->scopeConfig->getValue(
                self::XML_PATH_STORE_EMAIL,
                ScopeInterface::SCOPE_STORE,
                $storeId
            ));

            return $fallback !== '' ? [$fallback] : [];
        }

        $emails = preg_split('/[\s,;]+/', $configured) ?: [];
        $emails = array_values(array_filter(array_map('trim', $emails), static function (string $email): bool {
            return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
        }));

        return $emails;
    }

    public function isCustomerAcknowledgementEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SEND_ACK, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getFormTitle(?int $storeId = null): string
    {
        $title = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_FORM_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $title !== '' ? $title : (string) __('Product Enquiry');
    }

    public function getSuccessMessage(?int $storeId = null): string
    {
        $message = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_SUCCESS_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $message !== ''
            ? $message
            : (string) __('Thank you. Your enquiry has been submitted. We will contact you shortly.');
    }
}
