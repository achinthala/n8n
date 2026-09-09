<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Service;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Service to check admin permissions for quote editing
 */
class AdminQuotePermissionService
{
    /**
     * Configuration paths
     */
    const XML_PATH_QUANTITY_EDIT_ROLES = 'requestquote/admin_permissions/quantity_edit_roles';
    const XML_PATH_PRICING_CONTROL_ROLES = 'requestquote/admin_permissions/pricing_control_roles';
    const XML_PATH_FULL_DISCOUNT_ROLES = 'requestquote/admin_permissions/full_discount_roles';
    const XML_PATH_ADMIN_ASSISTANCE_ROLES = 'requestquote/admin_permissions/admin_assistance_roles';

    /**
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param AdminSession $adminSession
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        AdminSession $adminSession,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->adminSession = $adminSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get current admin user's role ID
     *
     * @return int|null
     */
    protected function getCurrentAdminRoleId(): ?int
    {
        if (!$this->adminSession->isLoggedIn()) {
            return null;
        }

        $user = $this->adminSession->getUser();
        if (!$user) {
            return null;
        }

        $role = $user->getRole();
        if (!$role) {
            return null;
        }

        return (int)$role->getRoleId();
    }

    /**
     * Get configured role IDs for a permission type
     *
     * @param string $configPath
     * @return array
     */
    protected function getConfiguredRoleIds(string $configPath): array
    {
        $roles = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($roles)) {
            return [];
        }

        if (is_string($roles)) {
            return explode(',', $roles);
        }

        return is_array($roles) ? $roles : [];
    }

    /**
     * Check if current admin has a specific permission
     *
     * @param string $configPath
     * @return bool
     */
    protected function hasPermission(string $configPath): bool
    {
        $currentRoleId = $this->getCurrentAdminRoleId();
        if (!$currentRoleId) {
            return false;
        }

        $allowedRoleIds = $this->getConfiguredRoleIds($configPath);
        if (empty($allowedRoleIds)) {
            // If no roles configured, deny access (secure by default)
            return false;
        }

        return in_array((string)$currentRoleId, $allowedRoleIds, true);
    }

    /**
     * Check if admin can edit quantities and remove items
     *
     * @return bool
     */
    public function canEditQuantity(): bool
    {
        return $this->hasPermission(self::XML_PATH_QUANTITY_EDIT_ROLES);
    }

    /**
     * Check if admin can remove items
     *
     * @return bool
     */
    public function canRemoveItems(): bool
    {
        return $this->hasPermission(self::XML_PATH_QUANTITY_EDIT_ROLES);
    }

    /**
     * Check if admin can control pricing, discounts, and shipping
     *
     * @return bool
     */
    public function canControlPricing(): bool
    {
        return $this->hasPermission(self::XML_PATH_PRICING_CONTROL_ROLES);
    }

    /**
     * Check if admin can apply discounts
     *
     * @return bool
     */
    public function canApplyDiscounts(): bool
    {
        return $this->hasPermission(self::XML_PATH_PRICING_CONTROL_ROLES);
    }

    /**
     * Check if admin can override shipping
     *
     * @return bool
     */
    public function canOverrideShipping(): bool
    {
        return $this->hasPermission(self::XML_PATH_PRICING_CONTROL_ROLES);
    }

    /**
     * Check if admin can apply 100% discount (zero cart total)
     *
     * @return bool
     */
    public function canApplyFullDiscount(): bool
    {
        return $this->hasPermission(self::XML_PATH_FULL_DISCOUNT_ROLES);
    }

    /**
     * Check if admin can edit Admin User Assistance post order
     *
     * @return bool
     */
    public function canEditAdminAssistance(): bool
    {
        return $this->hasPermission(self::XML_PATH_ADMIN_ASSISTANCE_ROLES);
    }

    /**
     * Check if admin can edit item prices
     *
     * @return bool
     */
    public function canEditItemPrices(): bool
    {
        return $this->hasPermission(self::XML_PATH_PRICING_CONTROL_ROLES);
    }

    /**
     * Get all permissions for current admin
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        return [
            'can_edit_quantity' => $this->canEditQuantity(),
            'can_remove_items' => $this->canRemoveItems(),
            'can_control_pricing' => $this->canControlPricing(),
            'can_apply_discounts' => $this->canApplyDiscounts(),
            'can_override_shipping' => $this->canOverrideShipping(),
            'can_apply_full_discount' => $this->canApplyFullDiscount(),
            'can_edit_admin_assistance' => $this->canEditAdminAssistance(),
            'can_edit_item_prices' => $this->canEditItemPrices(),
        ];
    }
}

