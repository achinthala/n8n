<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Api\Data;

interface ShareCartInterface
{
    public const SHARE_ID = 'share_id';
    public const TOKEN = 'token';
    public const YOUR_NAME = 'your_name';
    public const RECIPIENT_NAME = 'recipient_name';
    public const RECIPIENT_EMAIL = 'recipient_email';
    public const RECIPIENT_PHONE = 'recipient_phone';
    public const LINK_STATUS_ACTIVE = 'active';
    public const LINK_STATUS_EXPIRED = 'expired';
    public const LINK_STATUS_USED = 'used';
    public const LINK_STATUS_REVOKED = 'revoked';
    public const SHARED_BY_TYPE_CUSTOMER = 'customer';
    public const SHARED_BY_TYPE_ADMIN = 'admin';
    public const SHARED_BY_TYPE_GUEST = 'guest';
    public const RECIPIENT_CART_MODE_MERGE = 'merge';
    public const RECIPIENT_CART_MODE_REPLACE = 'replace';

    public function getShareId(): ?int;

    public function setShareId(int $shareId): self;

    public function getToken(): ?string;

    public function setToken(string $token): self;

    public function getLinkStatus(): ?string;

    public function setLinkStatus(string $status): self;

    public function getYourName(): ?string;

    public function setYourName(string $name): self;

    public function getRecipientName(): ?string;

    public function setRecipientName(string $name): self;

    public function getRecipientEmail(): ?string;

    public function setRecipientEmail(string $email): self;

    public function getRecipientPhone(): ?string;

    public function setRecipientPhone(string $phone): self;
}
