<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'dcw_share_cart/general/enabled';
    private const XML_PATH_LINK_EXPIRY_DAYS = 'dcw_share_cart/general/link_expiry_days';
    private const XML_PATH_RECIPIENT_CART_MODE = 'dcw_share_cart/general/recipient_cart_mode';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getLinkExpiryDays(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_LINK_EXPIRY_DAYS, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getRecipientCartMode(?int $storeId = null): string
    {
        $mode = (string) $this->scopeConfig->getValue(
            self::XML_PATH_RECIPIENT_CART_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return in_array($mode, [
            ShareCartInterface::RECIPIENT_CART_MODE_MERGE,
            ShareCartInterface::RECIPIENT_CART_MODE_REPLACE,
        ], true) ? $mode : ShareCartInterface::RECIPIENT_CART_MODE_MERGE;
    }
}
