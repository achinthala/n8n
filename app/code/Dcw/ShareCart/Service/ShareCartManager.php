<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Service;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Dcw\ShareCart\Helper\ContactFields;
use Dcw\ShareCart\Model\Config;
use Dcw\ShareCart\Model\Email\ShareCartEmailSender;
use Dcw\ShareCart\Model\ShareCart;
use Dcw\ShareCart\Model\ShareCartFactory;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;

class ShareCartManager
{
    public function __construct(
        private readonly ShareCartFactory $shareCartFactory,
        private readonly CartSnapshotBuilder $snapshotBuilder,
        private readonly LinkLifecycle $linkLifecycle,
        private readonly ShareCartEmailSender $emailSender,
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly AdminSession $adminSession,
        private readonly Config $config,
        private readonly ContactFields $contactFields,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $shareData
     * @throws LocalizedException
     */
    public function createShare(array $shareData): ShareCart
    {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('Share Cart is currently disabled.'));
        }

        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId() || $quote->getItemsCount() < 1) {
            throw new LocalizedException(__('Your cart is empty. Add items before sharing.'));
        }

        $shareCart = $this->buildShareRecord($quote, $shareData);
        $shareCart->save();

        $this->emailSender->send($shareCart);

        $this->logger->info('ShareCart created', [
            'share_id' => $shareCart->getShareId(),
            'link_status' => $shareCart->getLinkStatus(),
        ]);

        return $shareCart;
    }

    /**
     * @param array<string, mixed> $shareData
     */
    private function buildShareRecord(CartInterface $quote, array $shareData): ShareCart
    {
        /** @var ShareCart $shareCart */
        $shareCart = $this->shareCartFactory->create();
        $storeId = (int) $quote->getStoreId();
        $contactData = $this->contactFields->normalizeSaveData($shareData);

        $shareCart->setData(array_merge($contactData, [
            'store_id' => $storeId,
            'quote_id' => (int) $quote->getId(),
            'token' => $this->generateToken(),
            'shared_by_type' => $this->resolveSharedByType(),
            'shared_by_customer_id' => $this->customerSession->getCustomerId() ?: null,
            'shared_by_admin_user_id' => $this->adminSession->getUser() ? (int) $this->adminSession->getUser()->getId() : null,
            'shared_by_email' => $this->resolveSharedByEmail(),
            'sms_consent' => !empty($shareData['sms_consent']) ? 1 : 0,
            'custom_message' => trim((string) ($shareData['custom_message'] ?? '')),
            'cart_snapshot' => $this->snapshotBuilder->buildFromQuote($quote),
            'link_status' => ShareCartInterface::LINK_STATUS_ACTIVE,
            'expires_at' => $this->linkLifecycle->calculateExpiresAt($storeId),
        ]));

        return $shareCart;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function resolveSharedByType(): string
    {
        if ($this->adminSession->getUser()) {
            return ShareCartInterface::SHARED_BY_TYPE_ADMIN;
        }

        if ($this->customerSession->isLoggedIn()) {
            return ShareCartInterface::SHARED_BY_TYPE_CUSTOMER;
        }

        return ShareCartInterface::SHARED_BY_TYPE_GUEST;
    }

    private function resolveSharedByEmail(): ?string
    {
        if ($this->customerSession->isLoggedIn()) {
            return (string) $this->customerSession->getCustomer()->getEmail();
        }

        return null;
    }
}
