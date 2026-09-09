<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Model;

use Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Quote Change Log Model
 */
class QuoteChangeLog extends AbstractModel implements QuoteChangeLogInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\Dcw\RequestQuote\Model\ResourceModel\QuoteChangeLog::class);
    }

    /**
     * @inheritDoc
     */
    public function getLogId(): ?int
    {
        return $this->getData(self::LOG_ID) ? (int)$this->getData(self::LOG_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setLogId(int $logId): self
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId(): int
    {
        return (int)$this->getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId(int $quoteId): self
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getActionType(): string
    {
        return (string)$this->getData(self::ACTION_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setActionType(string $actionType): self
    {
        return $this->setData(self::ACTION_TYPE, $actionType);
    }

    /**
     * @inheritDoc
     */
    public function getItemId(): ?int
    {
        return $this->getData(self::ITEM_ID) ? (int)$this->getData(self::ITEM_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setItemId(?int $itemId): self
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * @inheritDoc
     */
    public function getOldValue(): ?string
    {
        return $this->getData(self::OLD_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setOldValue(?string $oldValue): self
    {
        return $this->setData(self::OLD_VALUE, $oldValue);
    }

    /**
     * @inheritDoc
     */
    public function getNewValue(): ?string
    {
        return $this->getData(self::NEW_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setNewValue(?string $newValue): self
    {
        return $this->setData(self::NEW_VALUE, $newValue);
    }

    /**
     * @inheritDoc
     */
    public function getChangedBy(): ?string
    {
        return $this->getData(self::CHANGED_BY);
    }

    /**
     * @inheritDoc
     */
    public function setChangedBy(?string $changedBy): self
    {
        return $this->setData(self::CHANGED_BY, $changedBy);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID) ? (int)$this->getData(self::CUSTOMER_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(?int $customerId): self
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getAdminId(): ?int
    {
        return $this->getData(self::ADMIN_ID) ? (int)$this->getData(self::ADMIN_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setAdminId(?int $adminId): self
    {
        return $this->setData(self::ADMIN_ID, $adminId);
    }

    /**
     * @inheritDoc
     */
    public function getAdminEmail(): ?string
    {
        return $this->getData(self::ADMIN_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setAdminEmail(?string $adminEmail): self
    {
        return $this->setData(self::ADMIN_EMAIL, $adminEmail);
    }

    /**
     * @inheritDoc
     */
    public function getAdminRole(): ?string
    {
        return $this->getData(self::ADMIN_ROLE);
    }

    /**
     * @inheritDoc
     */
    public function setAdminRole(?string $adminRole): self
    {
        return $this->setData(self::ADMIN_ROLE, $adminRole);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}

