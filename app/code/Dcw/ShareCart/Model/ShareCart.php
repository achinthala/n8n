<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Magento\Framework\Model\AbstractModel;

class ShareCart extends AbstractModel implements ShareCartInterface
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\ShareCart::class);
    }

    public function getShareId(): ?int
    {
        $id = $this->getData(self::SHARE_ID);

        return $id !== null ? (int) $id : null;
    }

    public function setShareId(int $shareId): ShareCartInterface
    {
        return $this->setData(self::SHARE_ID, $shareId);
    }

    public function getToken(): ?string
    {
        $token = $this->getData(self::TOKEN);

        return $token !== null ? (string) $token : null;
    }

    public function setToken(string $token): ShareCartInterface
    {
        return $this->setData(self::TOKEN, $token);
    }

    public function getLinkStatus(): ?string
    {
        $status = $this->getData('link_status');

        return $status !== null ? (string) $status : null;
    }

    public function setLinkStatus(string $status): ShareCartInterface
    {
        return $this->setData('link_status', $status);
    }

    public function getYourName(): ?string
    {
        $name = $this->getData(self::YOUR_NAME);

        return $name !== null ? (string) $name : null;
    }

    public function setYourName(string $name): ShareCartInterface
    {
        return $this->setData(self::YOUR_NAME, $name);
    }

    public function getRecipientName(): ?string
    {
        $name = $this->getData(self::RECIPIENT_NAME);

        return $name !== null ? (string) $name : null;
    }

    public function setRecipientName(string $name): ShareCartInterface
    {
        return $this->setData(self::RECIPIENT_NAME, $name);
    }

    public function getRecipientEmail(): ?string
    {
        $email = $this->getData(self::RECIPIENT_EMAIL);

        return $email !== null ? (string) $email : null;
    }

    public function setRecipientEmail(string $email): ShareCartInterface
    {
        return $this->setData(self::RECIPIENT_EMAIL, $email);
    }

    public function getRecipientPhone(): ?string
    {
        $phone = $this->getData(self::RECIPIENT_PHONE);

        return $phone !== null ? (string) $phone : null;
    }

    public function setRecipientPhone(string $phone): ShareCartInterface
    {
        return $this->setData(self::RECIPIENT_PHONE, $phone);
    }
}
