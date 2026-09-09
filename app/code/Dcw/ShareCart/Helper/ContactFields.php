<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Helper;

use Dcw\ShareCart\Model\ShareCart;
use Magento\Framework\DataObject;

class ContactFields
{
    public function getYourName(DataObject $share): string
    {
        $yourName = trim((string) $share->getData('your_name'));
        if ($yourName !== '') {
            return $yourName;
        }

        return trim((string) $share->getData('shared_by_name'));
    }

    public function getRecipientName(DataObject $share): string
    {
        $recipientName = trim((string) $share->getData('recipient_name'));
        if ($recipientName !== '') {
            return $recipientName;
        }

        return trim(trim((string) $share->getData('recipient_first_name')) . ' '
            . trim((string) $share->getData('recipient_last_name')));
    }

    public function getRecipientEmail(DataObject $share): string
    {
        return trim((string) $share->getData('recipient_email'));
    }

    public function getRecipientPhone(DataObject $share): string
    {
        $recipientPhone = trim((string) $share->getData('recipient_phone'));
        if ($recipientPhone !== '') {
            return $recipientPhone;
        }

        return trim((string) $share->getData('sender_phone'));
    }

    /**
     * @param array<string, mixed> $shareData
     * @return array<string, mixed>
     */
    public function normalizeSaveData(array $shareData): array
    {
        $yourName = trim((string) ($shareData['your_name'] ?? ''));
        $recipientName = trim((string) ($shareData['recipient_name'] ?? ''));
        $recipientEmail = trim((string) ($shareData['recipient_email'] ?? ''));
        $recipientPhone = trim((string) ($shareData['recipient_phone'] ?? ''));

        return [
            'your_name' => $yourName,
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'recipient_phone' => $recipientPhone,
            'shared_by_name' => $yourName,
            'sender_phone' => '',
            'recipient_first_name' => '',
            'recipient_last_name' => '',
        ];
    }
}
