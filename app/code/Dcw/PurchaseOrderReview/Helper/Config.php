<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
    }

    public const XML_PATH_ENABLED = 'dcw_po_review/general/enabled';
    public const XML_PATH_THRESHOLD = 'dcw_po_review/general/subtotal_threshold';
    public const XML_PATH_ALLOW_GUEST_PO = 'dcw_po_review/general/allow_guest_po';
    public const XML_PATH_ZENDESK_ENABLED = 'dcw_po_review/zendesk/enabled';
    public const XML_PATH_ZENDESK_SUBDOMAIN = 'dcw_po_review/zendesk/subdomain';
    public const XML_PATH_ZENDESK_EMAIL = 'dcw_po_review/zendesk/email';
    public const XML_PATH_ZENDESK_TOKEN = 'dcw_po_review/zendesk/api_token';
    public const XML_PATH_FALLBACK_EMAIL = 'dcw_po_review/fallback/email';

    public const XML_PATH_CHECKOUT_SHIPPING_MODAL_BLOCK = 'dcw_po_review/checkout/shipping_modal_cms_block';

    /** @var string Default CMS block identifier when config field is empty (create block manually in Admin). */
    public const DEFAULT_SHIPPING_MODAL_CMS_BLOCK_ID = 'po_shipping_modal';

    public function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSubtotalThreshold(?int $storeId = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isAllowGuestPo(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_GUEST_PO,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isZendeskEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ZENDESK_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getZendeskSubdomain(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ZENDESK_SUBDOMAIN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getZendeskApiEmail(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ZENDESK_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getZendeskApiToken(?int $storeId = null): string
    {
        $value = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_ZENDESK_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($value === '') {
            return '';
        }

        /**
         * Obscure + Encrypted fields are stored ciphertext in DB. Magento normally decrypts via
         * config metadata; if this path is not in metadata, ScopeConfig returns the raw ciphertext
         * (~90+ chars) and Zendesk returns 401. Detect Magento ciphertext and decrypt explicitly.
         */
        if (preg_match('/^\d+:\d+:/', $value)) {
            return trim((string) $this->encryptor->decrypt($value));
        }

        return $value;
    }

    public function getFallbackEmail(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_FALLBACK_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getShippingModalCmsBlockIdentifier(?int $storeId = null): string
    {
        $id = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_CHECKOUT_SHIPPING_MODAL_BLOCK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $id !== '' ? $id : self::DEFAULT_SHIPPING_MODAL_CMS_BLOCK_ID;
    }
}
