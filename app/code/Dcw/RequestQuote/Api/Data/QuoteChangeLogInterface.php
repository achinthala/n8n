<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Api\Data;

/**
 * Quote Change Log Interface
 */
interface QuoteChangeLogInterface
{
    public const LOG_ID = 'log_id';
    public const QUOTE_ID = 'quote_id';
    public const ACTION_TYPE = 'action_type';
    public const ITEM_ID = 'item_id';
    public const OLD_VALUE = 'old_value';
    public const NEW_VALUE = 'new_value';
    public const CHANGED_BY = 'changed_by';
    public const CUSTOMER_ID = 'customer_id';
    public const ADMIN_ID = 'admin_id';
    public const ADMIN_EMAIL = 'admin_email';
    public const ADMIN_ROLE = 'admin_role';
    public const CREATED_AT = 'created_at';

    public const ACTION_ITEM_ADDED = 'item_added';
    public const ACTION_ITEM_REMOVED = 'item_removed';
    public const ACTION_ITEM_UPDATED = 'item_updated';
    public const ACTION_ITEMS_MERGED = 'items_merged';
    public const ACTION_DISCOUNT_CHANGED = 'discount_changed';
    public const ACTION_DISCOUNT_CARRIED_OVER = 'discount_carried_over';
    public const ACTION_STATUS_CHANGED = 'status_changed';
    public const ACTION_QUOTE_EDITED = 'quote_edited';

    /**
     * Get log ID
     *
     * @return int|null
     */
    public function getLogId(): ?int;

    /**
     * Set log ID
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId): self;

    /**
     * Get quote ID
     *
     * @return int
     */
    public function getQuoteId(): int;

    /**
     * Set quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId(int $quoteId): self;

    /**
     * Get action type
     *
     * @return string
     */
    public function getActionType(): string;

    /**
     * Set action type
     *
     * @param string $actionType
     * @return $this
     */
    public function setActionType(string $actionType): self;

    /**
     * Get item ID
     *
     * @return int|null
     */
    public function getItemId(): ?int;

    /**
     * Set item ID
     *
     * @param int|null $itemId
     * @return $this
     */
    public function setItemId(?int $itemId): self;

    /**
     * Get old value
     *
     * @return string|null
     */
    public function getOldValue(): ?string;

    /**
     * Set old value
     *
     * @param string|null $oldValue
     * @return $this
     */
    public function setOldValue(?string $oldValue): self;

    /**
     * Get new value
     *
     * @return string|null
     */
    public function getNewValue(): ?string;

    /**
     * Set new value
     *
     * @param string|null $newValue
     * @return $this
     */
    public function setNewValue(?string $newValue): self;

    /**
     * Get changed by
     *
     * @return string|null
     */
    public function getChangedBy(): ?string;

    /**
     * Set changed by
     *
     * @param string|null $changedBy
     * @return $this
     */
    public function setChangedBy(?string $changedBy): self;

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): self;

    /**
     * Get admin ID
     *
     * @return int|null
     */
    public function getAdminId(): ?int;

    /**
     * Set admin ID
     *
     * @param int|null $adminId
     * @return $this
     */
    public function setAdminId(?int $adminId): self;

    /**
     * Get admin email
     *
     * @return string|null
     */
    public function getAdminEmail(): ?string;

    /**
     * Set admin email
     *
     * @param string|null $adminEmail
     * @return $this
     */
    public function setAdminEmail(?string $adminEmail): self;

    /**
     * Get admin role
     *
     * @return string|null
     */
    public function getAdminRole(): ?string;

    /**
     * Set admin role
     *
     * @param string|null $adminRole
     * @return $this
     */
    public function setAdminRole(?string $adminRole): self;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;
}

