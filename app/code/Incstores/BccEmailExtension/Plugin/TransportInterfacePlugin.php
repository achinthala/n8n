<?php

namespace Incstores\BccEmailExtension\Plugin;

use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Mail\MessageInterface;

class TransportInterfacePlugin
{
    private const HARDCODED_BCC_EMAILS = [
        'contact@flooringinc.com'
    ];

    public function beforeSendMessage(TransportInterface $subject)
    {
        $message = $subject->getMessage();

        if ($message instanceof MessageInterface) {
            foreach (self::HARDCODED_BCC_EMAILS as $bccEmail) {
                $message->addBcc($bccEmail);
            }
        }

        return [];
    }
}
