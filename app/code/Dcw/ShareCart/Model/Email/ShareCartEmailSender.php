<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Model\Email;

use Dcw\ShareCart\Helper\ContactFields;
use Dcw\ShareCart\Model\ShareCart;
use Dcw\ShareCart\ViewModel\ShareCartRestore;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ShareCartEmailSender
{
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder,
        private readonly ContactFields $contactFields,
        private readonly LoggerInterface $logger
    ) {
    }

    public function send(ShareCart $shareCart): void
    {
        $storeId = (int) $shareCart->getData('store_id');
        $store = $this->storeManager->getStore($storeId);
        $shareUrl = $this->buildShareUrl($storeId, (string) $shareCart->getToken());

        $yourName = $this->contactFields->getYourName($shareCart);
        $recipientName = $this->contactFields->getRecipientName($shareCart);
        $recipientEmail = $this->contactFields->getRecipientEmail($shareCart);
        $recipientPhone = $this->contactFields->getRecipientPhone($shareCart);

        $this->transportBuilder
            ->setTemplateIdentifier('dcw_share_cart_email')
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars([
                'store' => $store,
                'share_cart' => $shareCart,
                'share_url' => $shareUrl,
                'your_name' => $yourName,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'recipient_phone' => $recipientPhone,
                'custom_message' => (string) $shareCart->getData('custom_message'),
            ])
            ->setFromByScope('general', $storeId)
            ->addTo($recipientEmail, $recipientName ?: null)
            ->getTransport()
            ->sendMessage();

        $this->logger->info('ShareCart email sent', [
            'share_id' => $shareCart->getShareId(),
            'recipient_email' => $recipientEmail,
        ]);
    }

    private function buildShareUrl(int $storeId, string $token): string
    {
        $cartUrl = $this->urlBuilder->getUrl('checkout/cart', ['_scope' => $storeId]);

        return $cartUrl . '#' . ShareCartRestore::HASH_TOKEN_PARAM . '=' . rawurlencode($token);
    }
}
